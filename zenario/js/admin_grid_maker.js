/*
 * Copyright (c) 2015, Tribal Limited
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
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	zenarioG,
	formId, gridId, linksId, closeButtonId
) {
	"use strict";
	


zenarioG.editing = false;
zenarioG.data = {};
zenarioG.layoutName = '';
zenarioG.openedSkinId = '';
zenarioG.history = [];
zenarioG.pos = 0;
zenarioG.savedAtPos = 0;
zenarioG.lastIframeHeight = 360;
zenarioG.bodyPadding = 0;
zenarioG.desktopSmallestSize = 800;
zenarioG.previewPaddingLeftRight = 75;
zenarioG.gridAreaSmallestHeight = 150;

zenarioG.tabletWidth = 768;
zenarioG.mobileWidth = 320;

zenarioG.lastToast = false;



zenarioG.init = function(data, layoutId, layoutName, familyName) {
	if (typeof data == 'object') {
		zenarioG.data = data;
	} else {
		zenarioG.data = zenarioA.readData(data);
	}
	
	if (layoutName) {
		zenarioG.layoutName = layoutName;
	}
	if (layoutId) {
		zenarioG.layoutId = layoutId;
	}
	
	zenarioG.checkData(layoutName, familyName);
	zenarioG.checkDataR(zenarioG.data.cells);
	zenarioG.rememberNames(zenarioG.data.cells);
	
	//If nothing has been created yet, start on the editor
	if (!zenarioG.data.cells.length) {
		zenarioG.editing = true;
	}

	//Set up the close button, with a confirm if there are unsaved changes
	if (closeButtonId) {
		if (windowParent
		 && !windowParent.zenarioG
		 && windowParent.$
		 && windowParent.$.colorbox) {
		
			$('#' + closeButtonId).click(function() {
				if (zenarioG.savedAtPos == zenarioG.pos
				 || confirm(phrase.gridConfirmClose)) {
					windowParent.$.colorbox.close();
				}
			});
		} else {
			get(closeButtonId).style.display = 'none';
		}
	}
};

zenarioG.checkDataNonZero = function(warning, data, prop, defaultValue, min) {
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

zenarioG.checkDataNumeric = function(warning, data, prop, defaultValue, precision) {
	if (!precision) {
		precision = 1;
	}
	
	if (data[prop] === undefined || 1 * data[prop] != data[prop]) {
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
zenarioG.checkDataNonZeroAndNumeric = function(data, warning) {
	
	var warnFix = false,
		warnFlu = false;
	
	warning =	zenarioG.checkDataNonZero(warning, data, 'cols', 10, 0);
	warnFix =	zenarioG.checkDataNonZero(warnFix, data, 'colWidth', 60, 1);
	warnFlu =	zenarioG.checkDataNonZero(warnFlu, data, 'minWidth', 600, 100);
	warning =	zenarioG.checkDataNonZero(warning, data, 'maxWidth', 960, 100);
	warnFix =	zenarioG.checkDataNumeric(warnFix, data, 'gutter', 40);
	warnFix =	zenarioG.checkDataNumeric(warnFix, data, 'gutterLeftEdge', 0);
	warnFix =	zenarioG.checkDataNumeric(warnFix, data, 'gutterRightEdge', 0);
	warnFlu =	zenarioG.checkDataNumeric(warning, data, 'gutterFlu', 1, -10);
	warnFlu =	zenarioG.checkDataNumeric(warning, data, 'gutterLeftEdgeFlu', 0, -10);
	warnFlu =	zenarioG.checkDataNumeric(warning, data, 'gutterRightEdgeFlu', 0, -10);
	
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

zenarioG.checkDataR = function(cells) {
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
					grid_css_class: zenarioG.randomName(2, 'Grid_'),
					html: cells[j].after
				});
				delete cells[j].after;
			}
			
			cells[j].grid_break = true;
			cells[j].grid_css_class = ifNull(cells[j].name, zenarioG.randomName(2, 'Grid_'));
			delete cells[j].name;
			delete cells[j].grid_break_group;
		}
	}
	
	//The original data schema had no marker for where the slots were.
	//Add one as it makes things easier to follow
	for (i = 0; i < cells.length; ++i) {
		if (cells[i].name
		 && !cells[i].addCell
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
			zenarioG.checkDataR(cells[i].cells);
		
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


zenarioG.rememberNames = function(cells) {
	for (var i = 0; i < cells.length; ++i) {
		//Groupings
		if (cells[i].cells) {
			//Recursively check anything inside a grouping
			zenarioG.rememberNames(cells[i].cells);
		
		} else if (cells[i].name) {
			//When loading data, remember what the original name on the slots were
			cells[i].oName = cells[i].name;
		}
	}
};

zenarioG.checkData = function(layoutName, familyName) {
	var scale,
		cols,
		i, j, k;
	
	if (!zenarioG.data) {
		zenarioG.data = {cells: []};
	
	//Attempt to catch a case where data in an old format is loaded
	} else if (_.isArray(zenarioG.data) ) {
		zenarioG.data = {cells: zenarioG.data};
		
		if (familyName && (cols = 1*familyName.replace(/\D/g, ''))) {
			zenarioG.data.cols = cols;
		}
	
	} else if (!zenarioG.data.cells) {
		zenarioG.data.cells = [];
	}
	
	
	//If switching from a fixed grid to a flexi grid, try to migrate the existing settings to populate the new settings
	if (zenarioG.data.fluid
	 && zenarioG.data.gutterFlu === undefined
	 && zenarioG.data.gutter !== undefined) {
		zenarioG.checkDataNonZeroAndNumeric(zenarioG.data);
		
		scale = zenarioG.data.gutterLeftEdge + zenarioG.data.cols * zenarioG.data.colWidth + (zenarioG.data.cols - 1) * zenarioG.data.gutter + zenarioG.data.gutterRightEdge;
		
		if (scale > 960) {
			zenarioG.data.minWidth = 960;
		
		} else if (scale > 760) {
			zenarioG.data.minWidth = 760;
		
		} else {
			zenarioG.data.minWidth = scale;
		}
		zenarioG.data.maxWidth = scale;
		
		zenarioG.data.gutterFlu = Math.round(zenarioG.data.gutter / scale * 1000) / 10;
		zenarioG.data.gutterLeftEdgeFlu = Math.round(zenarioG.data.gutterLeftEdge / scale * 1000) / 10;
		zenarioG.data.gutterRightEdgeFlu = Math.round(zenarioG.data.gutterRightEdge / scale * 1000) / 10;
		
	//If switching from a flexi grid to a fixed grid, try to migrate the existing settings to populate the new settings
	} else
	if (!zenarioG.data.fluid
	 && zenarioG.data.gutter === undefined
	 && zenarioG.data.gutterFlu !== undefined) {
		zenarioG.checkDataNonZeroAndNumeric(zenarioG.data);
		
		scale = zenarioG.data.maxWidth;
		zenarioG.data.gutter = Math.round(zenarioG.data.gutterFlu * scale / 100);
		zenarioG.data.gutterLeftEdge = Math.round(zenarioG.data.gutterLeftEdgeFlu * scale / 100);
		zenarioG.data.gutterRightEdge = Math.round(zenarioG.data.gutterRightEdgeFlu * scale / 100);
		
		var selected,
			gutTotal = zenarioG.data.gutterLeftEdge + (zenarioG.data.cols - 1) * zenarioG.data.gutter + zenarioG.data.gutterRightEdge;
		
		zenarioG.data.colWidth = (scale - gutTotal) / zenarioG.data.cols;
		
		//Because percentage based numbers won't convert perfectly to pixels, we might be off of the total size slightly.
		//Attempt to correct this by changing the col-width/gutter to the next best values.
		if (selected = zenarioG.recalcColumnAndGutterOptions(zenarioG.data, true, scale)) {
			zenarioG.data.colWidth = selected.colWidth;
			zenarioG.data.gutter = selected.gutter;
		} else {
			delete zenarioG.data.colWidth;
			delete zenarioG.data.gutter;
		}
	}
	
	zenarioG.checkDataNonZeroAndNumeric(zenarioG.data);
	
	if (zenarioG.data.fluid) {
		delete zenarioG.data.gutter;
		delete zenarioG.data.gutterLeftEdge;
		delete zenarioG.data.gutterRightEdge;
		delete zenarioG.data.colWidth;
	} else {
		delete zenarioG.data.gutterFlu;
		delete zenarioG.data.gutterLeftEdgeFlu;
		delete zenarioG.data.gutterRightEdgeFlu;
	}
	
	//if (change) {
	//	change(zenarioG.data);
	//}
	
	if (!zenarioG.history.length) {
		zenarioG.pos = 0;
		zenarioG.history = [JSON.stringify(zenarioG.data)];
	}
};

zenarioG.draw = function(doNotRedrawForm) {
	if (!doNotRedrawForm) {
		zenarioG.drawForm();
	}
	
	if (zenarioG.editing) {
		zenarioG.drawEditor();
	} else {
		zenarioG.drawPreview();
	}
};

zenarioG.drawForm = function() {
	var m = {formId: formId},
		html = zenarioA.microTemplate('zenario_grid_maker_top', m);
		
	
	
	$('.ui-tooltip').remove();
	get(formId).innerHTML = html;
	zenarioA.tooltips('#' + formId);
	
	if (!zenarioG.data.fluid) {
		zenarioG.recalcColumnAndGutterOptions(zenarioG.data);
	}
	
	//Refresh the previews every time a field is changed
	$('#' + formId + ' input.zenario_grid_setting_min_width').change(zenarioG.recalcOnChange).spinner({stop: zenarioG.recalcOnInc, min: 100, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_max_width').change(zenarioG.recalcOnChange).spinner({stop: zenarioG.recalcOnInc, min: 320, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_flu').change(zenarioG.recalcOnChange).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_left_flu').change(zenarioG.recalcOnChange).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_right_flu').change(zenarioG.recalcOnChange).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 0.1}).after('<span class="zenario_grid_unit">%</span>');
	
	//For fixed grids, also recalculate the numbers every time a field is changed
	$('#' + formId + ' input.zenario_grid_setting_full_width').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 320, step: 10}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_cols').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 1, step: 1});
	$('#' + formId + ' input.zenario_grid_setting_gutter_left').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 1}).after('<span class="zenario_grid_unit">px</span>');
	$('#' + formId + ' input.zenario_grid_setting_gutter_right').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 1}).after('<span class="zenario_grid_unit">px</span>');
	
	$('#' + formId + ' select.zenario_grid_setting_col_and_gutter').change(zenarioG.recalcOnChange);
	
	//$('#' + formId + ' input.zenario_grid_setting_col_width').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 1, step: 1});
	//$('#' + formId + ' input.zenario_grid_setting_gutter').change(zenarioG.recalcOnChange).keyup(zenarioG.recalcOnKeyUp).spinner({stop: zenarioG.recalcOnInc, min: 0, step: 1});
	
	//Update the settings and redraw the UI when one of the settings buttons is pressed
	$('#' + formId + ' input.zenario_grid_setting_fixed').change(zenarioG.update);
	$('#' + formId + ' input.zenario_grid_setting_flu').change(zenarioG.update);
	$('#' + formId + ' input.zenario_grid_setting_responsive').change(zenarioG.update);
	$('#' + formId + ' input.zenario_grid_setting_mirror').change(zenarioG.update);
	
	
	if (zenarioG.canUndo()) {
		$('#' + formId + ' .zenario_grid_setting_undo').click(zenarioG.undo);
	}
	
	if (zenarioG.canRedo()) {
		$('#' + formId + ' .zenario_grid_setting_redo').click(zenarioG.redo);
	}
	
	$('#' + formId + ' .zenario_grid_setting_preview_grid').click(function() {
		zenarioG.editing = false;
		zenarioG.drawPreview();
	});
	
	$('#' + formId + ' .zenario_grid_setting_edit_slots').click(function() {
		zenarioG.editing = true;
		zenarioG.drawEditor();
	});
};

//Calucate valid width/gutter options for the select list
zenarioG.recalcColumnAndGutterOptions = function(data, justCalc, scale) {
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
		for (; colWidth > 0; --colWidth) {
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


zenarioG.AJAXsrc = function() {
	var data = JSON.stringify(zenarioG.data),
		src = URLBasePath + 'zenario/admin/grid_maker/ajax.php?data=' + encodeURIComponent(data);
	
	if (src.length > 2000) {
		src = URLBasePath + 'zenario/admin/grid_maker/ajax.php?cdata=' + zenario.nonAsyncAJAX(URLBasePath + 'zenario/admin/grid_maker/ajax.php', {compress: 1, data: data});
	}
	
	return src;
};

zenarioG.drawPreview = function() {
	zenarioG.checkData();
	
	var topHack = 5,
		leftHack = 10,
		min = 100,
		max = Math.max(zenarioG.desktopSmallestSize, Math.round(($(window).width() - zenarioG.previewPaddingLeftRight) / 100) * 100),
		startingValue = Math.min(max - 20, zenarioG.data.maxWidth),
		src = zenarioG.AJAXsrc(),
		m = {
			gridId: gridId,
			topHack: topHack,
			leftHack: leftHack,
			min: min,
			max: max,
			startingValue: startingValue,
			src: src
		},
		html = zenarioA.microTemplate('zenario_grid_maker_preview', m);
	
	$('#' + gridId).css('margin', 'auto');
	
	$('.ui-tooltip').remove();
	get(gridId).innerHTML = html;
	zenarioA.tooltips('#' + gridId);
	
	$('#' + gridId + '-slider').slider({
		min: min,
		max: max,
		step: 10,
		animate: true,
		value: startingValue,
		start:
			function(event, ui) {
				$('#' + gridId + '-slider_overlay').css('display', 'block');
			},
		stop:
			function(event, ui) {
				$('#' + gridId + '-slider_overlay').css('display', 'none');
			},
		slide:
			function(event, ui) {
				$('#' + gridId + '-slider_val').val(ui.value);
				$('#' + gridId + '-iframe').clearQueue();
				$('#' + gridId + '-size').clearQueue();
				
				if (event.originalEvent && (event.originalEvent.type == 'mousedown' || event.originalEvent.type == 'touchstart')) {
					$('#' + gridId + '-iframe').animate({width: ui.value});
					$('#' + gridId + '-size').animate({marginLeft: ui.value});
				} else {
					$('#' + gridId + '-iframe').width(ui.value);
					$('#' + gridId + '-size').css('marginLeft', ui.value + 'px');
				}
				
				zenarioG.resizePreview();
			}
	});
	
	var sliderValChange = function() {
		var val = 1 * this.value;
		
		if (val && val >= min && val <= max) {
			$('#' + gridId + '-slider').slider('value', val);
			$('#' + gridId + '-iframe').clearQueue().animate({width: val});
			$('#' + gridId + '-size').clearQueue().animate({marginLeft: val});
		}
	};
	
	$('#' + gridId + '-slider_val').change(sliderValChange);
	
	zenarioG.resizePreview();
	
	var changeFun = function(size) {
		$('#' + gridId + '-slider_val').val(size);
		$('#' + gridId + '-slider').slider('value', size);
		$('#' + gridId + '-size').clearQueue().animate({marginLeft: size});
		$('#' + gridId + '-iframe').clearQueue().animate({width: size}, {complete: zenarioG.resizePreview});
		
		if (zenarioG.lastToast) {
			zenarioG.lastToast.remove();
		}
		zenarioG.lastToast = toastr.info(phrase.gridDisplayingAt.replace('[[pixels]]', size));
	};
	
	$('#' + gridId + '-slider_mobile').click(
		function() {
			changeFun(zenarioG.mobileWidth);
		});
	
	$('#' + gridId + '-slider_tablet').click(
		function() {
			changeFun(zenarioG.tabletWidth);
		});
	
	$('#' + gridId + '-slider_desktop').click(
		function() {
			changeFun(max);
		});
	
	$('#' + formId + '--tabs').removeClass('zenario_grid_tabs_slots_selected').addClass('zenario_grid_tabs_grid_selected');
	zenarioG.drawLinks();
};

zenarioG.resizePreview = function() {
	var iframe, iframeHeight = 0;
	
	if ((iframe = get(gridId + '-iframe'))
	 && (iframe.contentWindow
	  && iframe.contentWindow.document
	  && iframe.contentWindow.document.body)) {
		var topHack = 5,
			leftHack = 10,
			initialHeight = 360,
			minWidth = 100,
			iframeHeight = ifNull($(iframe.contentWindow.document.body).height(), initialHeight);
		
		if(iframeHeight < initialHeight) iframeHeight = initialHeight;
		
		$('#' + gridId + '-iframe').height(iframeHeight);
		$('#' + gridId + '-slider_overlay').height(iframeHeight);
		$('#' + gridId + '-slider a')
			.css('position', 'absolute')
			.css('z-index', 20)
			.css('top', '-' + (iframeHeight + topHack) + 'px')
			.height(iframeHeight + topHack)
			.html('<span style="margin-top: ' + Math.ceil((iframeHeight + topHack) / 2) + 'px;"></span>');
	}
	
	zenarioG.lastIframeHeight = iframeHeight;
};


//Draw/redraw the boxes with controls to add, move and resize them
zenarioG.drawEditor = function(
	thisContId, thisCellId, 
	levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
) {
	var level = 0,
		gridScrollTop = 0;
	
	//zenarioG.data should contain all of the information on the boxes
	//You can have boxes within boxes, so this can be recursive
	//When we're drawing recursively, levels will contain an array of indices
	//that make up the current recursion path.
	if (levels && levels[0] !== undefined) {
		data = zenarioG.data;
		foreach (levels as var l) {
			l *= 1;
			level = l+1;
			data = data.cells[levels[l]];
		}
	} else {
		gridScrollTop = $('#' + gridId).scrollTop();
		zenarioG.checkData();
		data = zenarioG.data;
		level = 0;
		levels = [];
		zenarioG.names = {};
		zenarioG.randomNameCount = 0;
	}
	
	if (thisContId === undefined) {
		thisContId = gridId;
	}
	if (gCols === undefined) {
		gCols = zenarioG.data.cols;
	}
	if (gColWidth === undefined) {
		if (zenarioG.data.fluid) {
			//The UI only offers a fixed size, not a flexi size.
			
			//Remember some details on what the actual values should be
			gGutterWidthPercent = zenarioG.data.gutterFlu;
			gColWidthPercent = (100 - zenarioG.data.gutterLeftEdgeFlu - (zenarioG.data.cols - 1) * zenarioG.data.gutterFlu - zenarioG.data.gutterRightEdgeFlu) / zenarioG.data.cols;
			
			//Then convert fluid designs into fixed designs using 10px = 1%
			gGutter = Math.round(zenarioG.data.gutterFlu * zenarioG.data.maxWidth / 100);
			gGutterLeftEdge = Math.round(zenarioG.data.gutterLeftEdgeFlu * zenarioG.data.maxWidth / 100);
			gGutterRightEdge = Math.round(zenarioG.data.gutterRightEdgeFlu * zenarioG.data.maxWidth / 100);
			gGutterNested = gGutter;
			gColWidth = Math.round(gColWidthPercent * zenarioG.data.maxWidth / 100);
		
		} else {
			gGutterWidthPercent =
			gColWidthPercent = 0;
			gGutter = zenarioG.data.gutter;
			gGutterLeftEdge = zenarioG.data.gutterLeftEdge;
			gGutterRightEdge = zenarioG.data.gutterRightEdge;
			gGutterNested = gGutter;
			gColWidth = zenarioG.data.colWidth;
		}
	}
	
	
	var addCell,
		html = '',
		eyesThisLine = {},
		widthSoFarThisLine,
		largestWidth = 1,
		cells = data.cells,
		elId = gridId + '__cell' + levels.join('-'),
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
	
	
	//Each level should have a <ul> tag to contain it
	html += '<ul id="' + elId + 's" class="zenario_grids" style="width: ' + wrapWidth + 'px;';
		
	if (wrapPaddingLeft) {
		html += 'padding-left: ' + wrapPaddingLeft + 'px;';
	}
	if (wrapPaddingRight) {
		html += 'padding-right: ' + wrapPaddingRight + 'px;';
	}
	
	//If this is the outer-most tag, add a pink striped background so we can easily see the grid
	if (level == 0) {
		html += 'background: white top left repeat-y url(grid_bg.php?gColWidth=' + gColWidth + '&gCols=' + gCols + '&gGutter=' + gGutter + '&gGutterLeftEdge=' + gGutterLeftEdge + '&gGutterRightEdge=' + gGutterRightEdge + ');';
	}
		
	html += '">';
	
	if (level > 0) {
		
		html +=
			'<div class="zenario_grid_object_properties"><div><span' +
				' data-type="grouping"' +
				' data-for="' + thisCellId + '"' +
				' data-small="' + htmlspecialchars(data.small) + '"' +
				' data-is_full="' + engToBoolean(data.isAlpha && data.isOmega) + '"' +
				' data-is_last="' + engToBoolean(!data.isAlpha && data.isOmega) + '"' +
				' data-css_class="' + htmlspecialchars(data.css_class) + '"' +
				' title="' + phrase.gridEditProperties + '"' +
			'>' +
				(data.css_class?
					htmlspecialchars(data.css_class)
				 :	phrase.gridEditPlaceholder) +
			'</span></div></div><br/>';
	}
	
	if (!cells) {
		cells = [];
	}
	
	//Look out for empty nests, and remove them
	for (var i = cells.length - 1; i >= 0; --i) {
		if (cells[i].cells
		 && zenarioG.checkCellsEmpty(cells[i])) {
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
			cells[i].width = Math.min(gCols, ifNull(cells[i].width, 1));
		}
		
		widthSoFarThisLine += cells[i].width;
		if (widthSoFarThisLine > gCols) {
			widthSoFarThisLine = cells[i].width;
			
			if (lastI !== false) {
				cells[lastI].isOmega = true;
			}
			cells[i].isAlpha = true;
		}
		
		lastI = i;
	}
	
	//Add an "add cell" element onto the end
	addCell = cells.length;
	cells[addCell] = {addCell: true};
	
	//Add the "isOmega" class to the last cell on the line if needed, then make the "add" button full-width
	if (widthSoFarThisLine == gCols) {
		if (lastI !== false) {
			cells[lastI].isOmega = true;
		}
		cells[addCell].width = gCols;
		cells[addCell].isAlpha = true;
		cells[addCell].isOmega = true;
	} else {
		cells[addCell].width = gCols - widthSoFarThisLine;
		cells[addCell].isOmega = true;
	}
	
	//Slight modification - newly created slots should not be full-size.
	//(Nested cells should still be full-sized upon creation)
	if (level == 0) {
		//Make them about 2-columns wide (depending on the number of columns)
		var partialWidth = Math.max(1, Math.round(gCols / 5));
		
		//If there is less columns that this left, don't make the next column bigger
		//Also, if the are more columns that this left but not by much, allow the next cell
		//to be slightly bigger than usual to avoid making a sliver next time.
		if (cells[addCell].width > partialWidth * 1.5) {
			cells[addCell].width = partialWidth;
		}
	}
	
	
	//Draw each cell as a <li>, giving each a unique id, and storing some information against each
	//so we can work out which bit of the data the <li> matches up to
	foreach (cells as var i) {
		
		if (zenarioG.data.responsive) {
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
			- (cells[i].isOmega? wrapWidthAdjustmentRight : 0);
		
		cells[i].widthPercent = cells[i].width * gColWidthPercent + (cells[i].width - 1) * gGutterWidthPercent;
		
		
		cells[i].marginLeft = cells[i].isAlpha && wrapWidthAdjustmentLeft? gGutterLeftEdge : gGutterLeft;
		cells[i].marginRight = cells[i].isOmega && wrapWidthAdjustmentRight? gGutterRightEdge : gGutterRight;
		
		html += '<li';
			html += ' id="' + elId + '-' + i + '"';
			html += ' data-i="' + i + '"';
			html += ' data-level="' + level + '"';
			html += ' data-levels="' + levels.join('-') + '"';
			html += ' data-gutter="' + gGutter + '"';
			html += ' data-minwidth="' + (gColAndGutterWidth * 1 - gGutter) + '"';
			html += ' data-maxwidth="' + (gColAndGutterWidth * gCols - gGutter) + '"';
			html += ' data-col_and_gutter_width="' + gColAndGutterWidth + '"';
			html += ' data-displayed_width="' + (cells[i].width * gColAndGutterWidth - gGutter) + '"';
			html += ' data-displayed_margin_left="' + (cells[i].marginLeft) + '"';
			
			if (cells[i].addCell) {
				html += ' class="zenario_grid_add"';
				html += ' data-new-width="' + cells[i].width + '"';
			
			} else {
				var resizable = '';
				
				if (!cells[i].grid_break
				 && cells[i].small != 'only') {
					resizable = 'zenario_grid_cell_resizable';
				}
				
				html += ' class="zenario_grid_cell ' + resizable + ' zenario_grid_cell__' + elId;
				
				if (cells[i].flash && !zenarioG.undoing) {
					zenarioG.newlyAdded = elId + '-' + i;
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
			}
			
			html += ' style="';
				html += 'width: ' + cells[i].widthPx + 'px;';
		html += '">';
		
		
		
		
		
		if (cells[i].grid_break) {
			html += '<div class="zenario_grid_break_outer_wrap">';
		}
		
		var margin =
			'style="' +
				'margin-left: ' + cells[i].marginLeft + 'px; ' +
				'margin-right: ' + cells[i].marginRight + 'px;"';
		
		if (cells[i].addCell) {
			//Controls to add a new cell
			html += '<div class="zenario_add_cell zenario_grid_gutter" ' + margin + ' title="' + phrase.gridAdd + '">';
			html += '</div>';
		
		} else if (cells[i].cells) {
			//For cells that have children
			//Don't add any contents to this div as any contents will be replaced with the children
			html += '<div id ="' + elId + '-' + i + '-span"></div>';
		
		} else if (!cells[i].slot) {
			//Empty space
			html += '<div class="zenario_cell_in_grid zenario_grid_gutter ' +
				(cells[i].grid_break? 'zenario_grid_break zenario_grid_break_no_slot' : 'zenario_grid_space') + '" ' +
				margin +
				' title="' + (cells[i].grid_break? phrase.gridGridBreak : phrase.gridEmptySpace) + '">';
				
				html += '<div class="zenario_grid_delete" title="' + phrase.gridDelete + '" data-for="' + elId + '-' + i + '" style="right: ' + (cells[i].marginRight) + 'px;"></div>';
				html += '<div>&nbsp;</div>';
				html += '<div>&nbsp;</div>';

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
				cells[i].name = zenarioG.uniqueRandomName();
			
			} else if (!zenarioG.checkIfNameUsed(cells[i].name)) {
				zenarioG.registerNewName(cells[i].name);
				++zenarioG.randomNameCount;
			}
			
			//Draw the current size for cells that should have a slot
			html += '<div class="zenario_grid_border zenario_cell_in_grid zenario_grid_gutter ' + (cells[i].grid_break? 'zenario_grid_break zenario_grid_break_with_slot' : '') + '" ' + margin + '>';
				
				html += '<div class="zenario_grid_name_area">';
					html += '<div class="zenario_grid_cell_info" title="' + (cells[i].grid_break? phrase.gridGridBreakWithSlot : phrase.gridSlot) + '" style="left: ' + (cells[i].marginLeft) + 'px;"></div>';
					html += '<div class="zenario_grid_delete" title="' + phrase.gridDelete + '" data-for="' + elId + '-' + i + '" style="right: ' + (cells[i].marginRight) + 'px;"></div>';
					html += '<div class="zenario_grid_faux_block_t"></div>' +
							'<div class="zenario_grid_object_properties"><div><span' +
								' data-type="' + (cells[i].grid_break? 'grid_break_with_slot' : 'slot') + '"' +
								' data-for="' + elId + '-' + i + '"' +
								' data-name="' + htmlspecialchars(cells[i].name) + '"' +
								' data-small="' + htmlspecialchars(cells[i].small) + '"' +
								' data-height="' + htmlspecialchars(cells[i].height) + '"' +
								' data-is_full="' + engToBoolean(cells[i].isAlpha && cells[i].isOmega) + '"' +
								' data-is_last="' + engToBoolean(!cells[i].isAlpha && cells[i].isOmega) + '"' +
								' data-css_class="' + htmlspecialchars(cells[i].css_class) + '"';
				
						if (cells[i].grid_break) {
							html += ' data-grid_css_class="' + htmlspecialchars(cells[i].grid_css_class) + '"';
						}
						html +=
								' title="' + phrase.gridEditProperties + '"' +
							'>' +
								htmlspecialchars(cells[i].name) +
								(cells[i].height && cells[i].height != 'small'? ' (' + cells[i].height + ')': '') + 
							'</span></div></div>';
				html += '</div>';
				
				html += '<div class="zenario_grid_faux_block_r"></div>';
				html += '<div class="zenario_grid_cell_size">';
				
				if (!cells[i].grid_break) {
					var size = cells[i].width * gColAndGutterWidth - gGutter;
					if (zenarioG.data.fluid) {
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
				html += '<div class="zenario_grid_faux_block_t"></div>';
			
			html += '</div>';
		}
		
		if (cells[i].grid_break) {
			html += '</div>';
		}
		
		html += '</li>';
	}
	
	html += '</ul>';
	
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
			
			/*
			if (zenarioG.data.fluid) {
				gGutterNested = gGutterLeftNested = gGutterRightNested = 0;
				gGutterLeftNested = cells[i].marginLeft;
				gGutterRightNested = cells[i].marginRight;
				
				childColWidth = Math.round(editorWidth / cells[i].width);
			}
			*/
			
/*
zenarioG.drawEditor = function(
	thisContId, levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
*/

			levels.push(i);
			var largestSubWidth =
					zenarioG.drawEditor(
						elId + '-' + i + '-span', elId + '-' + i,
						levels,
						gColsNested, childColWidth, gGutterNested,
						cells[i].isAlpha? gGutterLeftEdge - wrapPaddingLeft : gGutterLeftNested,
						cells[i].isOmega? gGutterRightEdge - wrapPaddingRight : gGutterRightNested,
						gGutterNested,
						cells[i].widthPercent / gColsNested, 0);
			levels.pop();
			
			//Remember the largest width of the child cells that we saw.
			//When resizing, it shouldn't be possible to shrink this cell smaller than this size
			if (largestSubWidth > 1) {
				$('#' + elId + '-' + i).attr('data-minwidth', (gColAndGutterWidth * largestSubWidth - gGutter));
				cells[i].width = Math.max(cells[i].width, largestSubWidth);
			}
		}
		
		if (!cells[i].addCell) {
			largestWidth = Math.max(largestWidth, cells[i].width);
		}
		
		//Remove our isAlpha and isOmega flags from the data
		delete cells[i].isAlpha;
		delete cells[i].isOmega;
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
	foreach (eyesThisLine as eye) {
		maxHeight = Math.max(maxHeight, $('#' + elId + '-' + eye).height());
	}
	foreach (eyesThisLine as eye) {
		$('#' + elId + '-' + eye).height(maxHeight);
	}
	
	
	//Remove the "add" buttons from the data
	cells.pop();
	
	//Make all the cells for the current container sortable
	$('#' + elId + 's').disableSelection();
	$('#' + elId + 's').sortable({
		cancel: 'a,button,input,select',
		placeholder: 'zenario_grid_cell zenario_grid_sortable_target',
		sort: function(event, ui) {
			var target = $('.zenario_grid_sortable_target'),
				top = 10,
				bottom = 14,
				width = ui.item.width(),
				height = ui.item.height(),
				left = 1*ui.item.attr('data-displayed_margin_left'),
				right = width - left - 1*ui.item.attr('data-displayed_width');
			
			target.height(height);
			height -= top + bottom;
			
			target
				.width(width)
				.html('<div style="margin: ' + top + 'px ' + right + 'px ' + bottom + 'px ' + left + 'px; height: ' + height + 'px;"></div>');
		},
		
		//Only allow sorting on grid elements, and not the add-controls
		items: 'li.zenario_grid_cell__' + elId,
		
		//Function to handle a reordering
		start: function(event, ui) {
			//Stop the rename event firing at the same time
			zenarioG.stopRenames = true;
		},
			
		stop: function(event, ui) {
			//Get an array containing the new sort order
			var newOrder = $('#' + elId + 's').sortable('toArray'),
				newData = [],
				data = zenarioG.data,
				adjustedForLevelYet = false;
			
			//Loop through each newly-sorted element
			foreach (newOrder as var n) {
				var el = $('#' + newOrder[n]),
					i = el.attr('data-i'),
					levels = el.attr('data-levels');
				
				//The data variable should be a pointer to the right location in the zenarioG.data object.
				//(This will either be the zenarioG.data object or a subsection if the cells being re-ordered are
				// children of another cell.)
				//The information telling us where this should be is stored against each item, so we have to set this
				//pointer while looping through the elements, but we need only do it once.
				if (!adjustedForLevelYet) {
					adjustedForLevelYet = true;
					if (levels) {
						levels = levels.split('-');
						foreach (levels as var l) {
							l *= 1;
							data = data.cells[1*levels[l]];
						}
					}
				}
				
				//Come up with a new object, with the current data in the new order...
				newData[n] = data.cells[1*i];
			}
			
			//...then replace the new object with the current data.
			data.cells = newData;
			
			toastr.success(phrase.growlSlotMoved);
			
			zenarioG.change();
		}
	});
	
	//Add some logic that's run against everything
	if (level == 0) {
		$('#' + formId + '--tabs').removeClass('zenario_grid_tabs_grid_selected').addClass('zenario_grid_tabs_slots_selected');
		
		//Make all the cells (except responsive children) resizable
		//(Note that this logic glitches out if you call it recursively, it must be run at the end on everything at once.)
		$('#' + thisContId + ' .zenario_grid_cell_resizable').each(function(i, el) {
			var offset = $(el).width() - 1 * $(el).attr('data-displayed_width'),
				gColAndGutterWidth = 1 * $(el).attr('data-col_and_gutter_width'),
				marginLeft = 1 * $(el).attr('data-displayed_margin_left'),
				minWidth = 1 * $(el).attr('data-minwidth'),
				maxWidth = 1 * $(el).attr('data-maxwidth'),
				level = 1 * $(el).attr('data-level');
			
			$(el).resizable({
				cancel: 'a,button,input,select',
				handles: 'se',
				grid: gColAndGutterWidth,
				minHeight: $(el).height(),
				maxHeight: $(el).height(),
				
				//Here I actually want minWidth and maxWidth to be equal to minWidth and maxWidth,
				//but there is some sort of weird rounding error in jQuery 1.10, so I'm adding half
				//a column's padding either way. They should still round and snap to the right values.
				minWidth: Math.floor(minWidth - 0.5 * gColAndGutterWidth + 1),
				maxWidth: Math.floor(maxWidth + 0.5 * gColAndGutterWidth - 1),
				helper: "ui-resizable-helper",
				
				start: function(event, ui) {
					if ($(el).hasClass('zenario_grid_nest')) {
						$(document.body).addClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					
					} else if ($(el).hasClass('zenario_grid_space_cell')) {
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
						if (!zenarioG.data.fluid) {
							html += size + 'px';
						
						} else {
							html += '~' + Math.round(size * 100 / zenarioG.data.maxWidth) + '%';
						}
					}
					
					ui.helper.html(
						'<div class="zenario_resizing_border" style="height: ' + (ui.helper.height() - 4) + 'px; width: ' + (size - 4) + 'px; margin-left: ' + marginLeft + 'px;">' +
							'<div class="zenario_size_when_resizing">' + html + '</div>' +
						'</div>');
				},
				
				//Function to update the data when we're finished resizing
				stop: function(event, ui) {
						$(document.body).removeClass('zenario_grid_resizing_nest').removeClass('zenario_grid_resizing_cell').removeClass('zenario_grid_resizing_space_cell');
					
					var i = ui.element.attr('data-i'),
						levels = ui.element.attr('data-levels'),
						data = zenarioG.data;
					
					//The data variable should be a pointer to the right location in the zenarioG.data object.
					//(This will either be the zenarioG.data object or a subsection if the cells being re-ordered are
					// children of another cell.)
					if (levels) {
						levels = levels.split('-');
						foreach (levels as var l) {
							l *= 1;
							data = data.cells[1*levels[l]];
						}
					}
					
					//Update the width of the cell that was just resized
					data.cells[1*i].width = Math.round((ui.element.width() + $(el).attr('data-gutter') / 2) / gColAndGutterWidth);
					zenarioG.change();
				}
			});
		});
		
		//Hack to set the correct position for the resize handles - unlike the delete button I can't set this manually when drawing the HTML
		$('#' + thisContId + ' .ui-resizable-handle').each(function(i, el) {
			
			var handle = $(el),
				prev = handle.prev();
			
			handle.css('right', prev.css('marginRight').replace(/\D/g, '') * 1);
			
			if (prev.hasClass('zenario_grids')) {
				//For nested cells, put the resize tool for the nest at the very bottom of the nest
				handle.css('bottom', '0');
				handle.attr('title', phrase.gridResizeNestedCells);
			
			} else {
				//For cells, put the resize tool at the bottom of the visible section
					//To get this right, we'll need to take the height of the visible section,
					//then manually add in the upper margin, upper border, upper padding, lower padding and lower border,
					//then subtract the height of the resize button.
				handle.css('top', (prev.height() + 10 + 2 + 10 + 10 + 2 - 19) + 'px');
				handle.attr('title', phrase.gridResizeSlot);
			}
		});
		
		//Set the width of the overal content to the grid's full width, and add tooltips
		$('#' + thisContId).width(wrapWidth + wrapPaddingLeft + wrapPaddingRight);
		zenarioA.tooltips('#' + thisContId);
		
		//Attach the delete function to the delete buttons
		$('#' + thisContId + ' .zenario_grid_delete').click(function() {
			zenarioG.deleteCell(this); 
		});
		
		//Attach a colorbox to the add buttons, put three links in the colorbox (one for each type of thing to add),
		//and finally attach the add function to each link.
		$('#' + thisContId + ' .zenario_grid_add').click(function() {
			var el = this,
				$el = $(el),
				level = $el.attr('data-level'),
				cssClasses = ['zenario_grid_box', 'zenario_grid_add_cell_box'],
				m = {
					level: level
				},
				html = zenarioA.microTemplate('zenario_grid_maker_add_new_object', m);
			
			
			$.colorbox({
				transition: 'none',
				html: html,
				
				onOpen: function() { zenario.addClassesToColorbox(cssClasses); },
				onClosed: function() { zenario.removeClassesToColorbox(cssClasses); },
				
				onComplete: function() {
					$('a.zenario_grid_add_slot').click(function() {
						zenarioG.add(el, 'slot'); 
					});
					$('a.zenario_grid_add_space').click(function() {
						zenarioG.add(el, 'space'); 
					});
					$('a.zenario_grid_add_children').click(function() {
						zenarioG.add(el, 'grouping'); 
					});
					$('a.zenario_grid_add_grid_break').click(function() {
						zenarioG.add(el, 'grid_break'); 
					});
					$('a.zenario_grid_add_grid_break_with_slot').click(function() {
						zenarioG.add(el, 'grid_break_with_slot'); 
					});
				}
			});
		});
		
		//Add another colorbox to the slot names, that allows you to rename a slot name by clicking on it
		$('#' + thisContId + ' .zenario_grid_object_properties span').click(function() {
			if (!zenarioG.stopRenames) {
				var el = this,
					$el = $(el),
					cssClasses = ['zenario_grid_box', 'zenario_grid_rename_slot_box'],
					m = {
						type: $el.attr('data-type'),
						name: $el.attr('data-name'),
						small: $el.attr('data-small'),
						height: $el.attr('data-height'),
						is_full: $el.attr('data-is_full'),
						is_last: $el.attr('data-is_last'),
						css_class: $el.attr('data-css_class'),
						html: $el.attr('data-html'),
						grid_css_class: $el.attr('data-grid_css_class')
					};
				
				$.colorbox({
					transition: 'none',
					html: zenarioA.microTemplate('zenario_grid_maker_object_properties', m),
					
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
							
							zenarioG.saveProperties(el, o);
						});
						$('.zenario_grid_properties_form input,textarea')[1].focus();
					}
				});
			}
		});
		
		//Workaround for a bug where if you click and drag on a label,
		//you are then prompted to rename it immediate afterwards
		setTimeout(function() { delete zenarioG.stopRenames; }, 100);
		
		zenarioG.drawLinks();
		
		//Fade in any newly added objects
		$('#' + thisContId + ' .zenario_grid_newly_added .zenario_cell_in_grid').effect({effect: 'highlight', duration: 1000, easing: 'zenarioOmmitEnd'});
		
		//Scroll the page down slightly to show any newly adding objects
		if (zenarioG.newlyAdded) {
			gridScrollTop += $('#' + zenarioG.newlyAdded).height();
			delete zenarioG.newlyAdded;
		}
		
		$('#' + gridId).scrollTop(gridScrollTop);
	
	} else {
		return largestWidth;
	}
};

zenarioG.drawLinks = function() {
	zenarioG.checkData();
	
	var src = zenarioG.AJAXsrc(),
		m = {
			src: src
		},
		html = zenarioA.microTemplate('zenario_grid_maker_bottom_links', m);
	
	$('.ui-tooltip').remove();
	get(linksId).innerHTML = html;
	zenarioA.tooltips('#' + linksId);
	
	
	//The links and form will have a set height.
	//Work out the height of the window, and give the preview/editor the remaining height.
	var sel = $('#' + gridId);
	if (zenarioG.editing) {
		sel.addClass('zenario_grid_slot_view').removeClass('zenario_grid_grid_view');
	
	} else {
		sel.addClass('zenario_grid_grid_view').removeClass('zenario_grid_slot_view');
		sel.css({height: 'auto'});
		sel = $('#' + gridId + ' .zenario_grid_preview_frame');
	}
	
	sel.height(0);
	sel.height(Math.max(zenarioG.gridAreaSmallestHeight, $(window).height() - $('body').height() - zenarioG.bodyPadding));
	
	
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
	
	
	if (window.zenarioAdminHasSavePerms) {
		$('#' + linksId + ' .zenario_grid_save_tf .zenario_grid_save a').click(function() { zenarioG.save(); });
		$('#' + linksId + ' .zenario_grid_save_tf .zenario_grid_save_as a').click(function() { zenarioG.save(true); });
	}
};

zenarioG.checkCellsEmpty = function(data) {
	if (!data || !data.cells) {
		return true;
	}
	
	var i = undefined;
	foreach (data.cells as i) {
		break;
	}
	
	return i === undefined;
};


//Allow an Admin to save a grid design to the database
zenarioG.save = function(saveAs) {
	
	if (!zenarioG.layoutId) {
		saveAs = true;
	}
	
	var src = zenarioG.AJAXsrc(),
		data;
	
	if (saveAs) {
		var cssClasses = ['zenario_grid_box', 'zenario_grid_new_layout_box'];
		
		if (data = zenario.nonAsyncAJAX(src, {saveas: 1, layoutId: zenarioG.layoutId}, true)) {
			
			$.colorbox({
				transition: 'none',
				html: zenarioA.microTemplate('zenario_grid_maker_save_prompt', {name: ifNull(zenarioG.newLayoutName, data.oldLayoutName, '')}),
				
				onOpen: function() { zenario.addClassesToColorbox(cssClasses); },
				onClosed: function() { zenario.removeClassesToColorbox(cssClasses); },
				
				onComplete: function() {
					$('#zenario_grid_new_layout_save').click(function() {
						$('#zenario_grid_error').hide();
						
						if (data = zenario.nonAsyncAJAX(src, {
							saveas: 1,
							confirm: 1,
							layoutId: zenarioG.layoutId,
							layoutName: zenarioG.newLayoutName = get('zenario_grid_layout_name').value
						}, true)) {
							if (data.error) {
								$('#zenario_grid_error').html(htmlspecialchars(data.error)).slideDown();
							} else {
								$.colorbox.close();
								zenarioG.layoutName = zenarioG.newLayoutName;
								zenarioG.markAsSaved(data);
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
		if (data = zenario.nonAsyncAJAX(src, {save: 1, layoutId: zenarioG.layoutId}, true)) {
			if (data.success) {
				zenarioG.markAsSaved(data, true);
			
			} else {
				zenarioA.floatingBox(data.message, phrase.gridSave, 'warning', true, true);
				
				$('#zenario_fbMessageButtons .submit_selected').click(function() {
					setTimeout(function() {
						var data;
						if (data = zenario.nonAsyncAJAX(src, {save: 1, layoutId: zenarioG.layoutId, confirm: 1}, true)) {
							zenarioG.markAsSaved(data);
						}
					}, 50);
				});
			}
		}
	}
};

//Remember that we last saved at this point in the undo-history
zenarioG.markAsSaved = function(data, useMessageBoxForSuccessMessage) {
	
	zenarioG.savedAtPos = zenarioG.pos;
	
	if (data.layoutId !== undefined) {
		zenarioG.layoutId = data.layoutId;
	}
	
	//Re-record the cell names
	zenarioG.rememberNames(zenarioG.data.cells);
	
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
zenarioG.readSettings = function(data) {
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
zenarioG.update = function() {
	zenarioG.readSettings(zenarioG.data);
	zenarioG.checkData();
	zenarioG.change();
};


zenarioG.recalcOnKeyUp = function() {
	zenarioG.recalc(this);
};

zenarioG.recalcOnChange = function() {
	zenarioG.recalc(this, true, true);
};

zenarioG.recalcOnInc = function(event, ui) {
	if (event.originalEvent && event.originalEvent.type == 'mouseup') {
		return zenarioG.recalc(this, true);
	}
};

zenarioG.recalc = function(el, redraw, forceChange) {
	var data = $.extend(false, {}, zenarioG.data),
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
	zenarioG.readSettings(data);
	warning = zenarioG.checkDataNonZeroAndNumeric(data, warning);
	
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
					if (newData = zenarioG.recalcColumnAndGutterOptions(data)) {
						data.colWidth = newData.colWidth;
						data.gutter = newData.gutter;
					}
				}
				
				//Validate the newly changed settings
				warning = zenarioG.checkDataNonZeroAndNumeric(data, warning);
				
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
				zenarioG.revert();
			} else {
				//If there were no errors, upgrade the grid straight away
				zenarioG.readSettings(zenarioG.data);
				zenarioG.checkDataNonZeroAndNumeric(zenarioG.data);
				zenarioG.change(false, true);
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


//Delete something
zenarioG.deleteCell = function(el) {
	//Try to get the cell that the delete button was for
	var cell;
	if ((cell = $(el).attr('data-for'))
	 && (cell = $('#' + cell))) {
		var i = cell.attr('data-i'),
			levels = cell.attr('data-levels'),
			data = zenarioG.data;
		
		//The data variable should be a pointer to the right location in the zenarioG.data object.
		//(This will either be the zenarioG.data object or a subsection if the cells being re-ordered are
		// children of another cell.)
		if (levels) {
			levels = levels.split('-');
			foreach (levels as var l) {
				l *= 1;
				data = data.cells[1*levels[l]];
			}
		}
		
		//Remove the deleted element
		data.cells.splice(i, 1);
		zenarioG.change();
		toastr.success(phrase.growlSlotDeleted);
	}
};

//Add something
zenarioG.add = function(el, type, respClass) {
	//Try to get the level that the add button was for
	var levels = $(el).attr('data-levels'),
		newWidth = ifNull($(el).attr('data-new-width'), 1),
		data = zenarioG.data;
	
	$.colorbox.close();
		
	if (levels !== undefined && levels !== false) {
		//The data variable should be a pointer to the right location in the zenarioG.data object.
		//(This will either be the zenarioG.data object or a subsection if the cells being re-ordered are
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
				name: zenarioG.uniqueRandomName(),
				flash: true
			});
			toastr.success(phrase.growlSlotAdded);
		
		} else if (type == 'space') {
			data.cells.push({
				width: newWidth,
				space: true,
				css_class: zenarioG.randomName(2, 'Space_'),
				flash: true
			});
			toastr.success(phrase.growlSpaceAdded);
		
		} else if (type == 'grouping') {
			data.cells.push({
				width: newWidth,
				css_class: zenarioG.randomName(2, 'Grouping_'),
				flash: true,
				cells: [
					{width: newWidth, slot: true, name: zenarioG.uniqueRandomName()},
					{width: newWidth, slot: true, name: zenarioG.uniqueRandomName()}
				]
			});
			toastr.success(phrase.growlChildrenAdded);
		
		} else if (type == 'grid_break') {
			data.cells.push({
				width: data.cols,
				grid_break: true,
				grid_css_class: zenarioG.randomName(2, 'Grid_'),
				flash: true
			});
			toastr.success(phrase.growlGridBreakAdded);
		
		} else if (type == 'grid_break_with_slot') {
			data.cells.push({
				width: data.cols,
				grid_break: true,
				slot: true,
				grid_css_class: zenarioG.randomName(2, 'Grid_'),
				name: zenarioG.uniqueRandomName(),
				flash: true
			});
			toastr.success(phrase.growlSlotAdded);
		}
		
		zenarioG.change();
	}
};

//Rename a slot
zenarioG.saveProperties = function(el, params) {
	//Try to get the cell that the name was for
	var cell;
	if ((cell = $(el).attr('data-for'))
	 && (cell = $('#' + cell))) {
		var i = cell.attr('data-i'),
			levels = cell.attr('data-levels'),
			data = zenarioG.data;
		
		//The data variable should be a pointer to the right location in the zenarioG.data object.
		//(This will either be the zenarioG.data object or a subsection if the cells being re-ordered are
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
		
			} else if (params.name != data.cells[i].name && zenarioG.checkIfNameUsed(params.name)) {
				$('#zenario_grid_error').html(phrase.gridErrorNameInUse).slideDown();
				return;
			}
			
			//Rename the slot
			delete zenarioG.names[data.cells[i].name];
			zenarioG.registerNewName(params.name);
		}
		
		foreach (params as var j) {
			if (params[j] != '') {
				data.cells[i][j] = params[j];
			} else {
				delete data.cells[i][j];
			}
		}

		zenarioG.change();
	}
	
	$.colorbox.close();
};

//Generate a random slot name
zenarioG.randomName = function(length, prefix) {
	var aToZ = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
		text = ifNull(prefix, 'Slot_');
	
	for (var i = 0; i < length; ++i) {
		text += aToZ.charAt(Math.floor(Math.random() * 26));
	}
	
	return text;
};

//Keep calling the zenarioG.randomName() function until we get an unused one
	//This would be very inefficient were I using the complete space of random names as the space filled up,
	//but as soon as we've had log-10 names I switch up to using another digit, so the space is always a lot less than half full.
zenarioG.uniqueRandomName = function() {
	var name,
		length = Math.ceil(Math.log(Math.max(zenarioG.randomNameCount, 2)) / Math.log(10));
	
	do {
		name = zenarioG.randomName(length);
	} while (zenarioG.checkIfNameUsed(name));
	
	zenarioG.names[name] = true;
	++zenarioG.randomNameCount;
	return name;
};

zenarioG.checkIfNameUsed = function(name) {
	return zenarioG.names[name.toLowerCase()] !== undefined;
};

zenarioG.registerNewName = function(name) {
	return zenarioG.names[name.toLowerCase()] = name;
};

//Redraw the editor
zenarioG.change = function(historic, doNotRedrawForm) {
	
	//Call the onchange function if there is one for this editor
	//if (change) {
	//	change(zenarioG.data);
	//}
	
	//Add this change to the history (unless this change was triggered by going through the history).
	if (!historic) {
		//If we have been previously navigating the history, forget any future changes that were undone
		if (zenarioG.pos < zenarioG.history.length - 1) {
			zenarioG.history.splice(zenarioG.pos + 1, zenarioG.history.length - 1 - zenarioG.pos);
		}
		
		//Add the current state to the history
		zenarioG.history.push(JSON.stringify(zenarioG.data));
		
		//Set our currently position to the current state in the history
		zenarioG.pos = zenarioG.history.length - 1;
		
		//Check if we've just destoryed a point in the undo history where we saved.
		//If so, clear the pointer that recorded that we'd saved there.
		if (zenarioG.savedAtPos >= zenarioG.pos) {
			zenarioG.savedAtPos = -1;
		}
	}
	
	zenarioG.draw(doNotRedrawForm);
};

//Go backwards or forwards in the history by n steps
zenarioG.undoOrRedo = function(n) {
	var pos = zenarioG.pos - n;
	
	if (pos < 0 || pos >= zenarioG.history.length) {
		return;
	}
	
	zenarioG.pos = pos;
	zenarioG.data = zenarioA.readData(zenarioG.history[pos]);
	zenarioG.change(true, n > 1);
};

zenarioG.undo = function() {
	zenarioG.undoing = true;
	zenarioG.undoOrRedo(1);
	delete zenarioG.undoing;
};

zenarioG.redo = function() {
	zenarioG.undoOrRedo(-1);
};

zenarioG.revert = function() {
	zenarioG.undoOrRedo(0);
};

zenarioG.canUndo = function() {
	return zenarioG.pos > 0;
};

zenarioG.canRedo = function() {
	return zenarioG.pos < zenarioG.history.length - 1;
};







},
	window.zenarioG = function() {},
	'settings', 'grid', 'download_links', 'close_button'
);