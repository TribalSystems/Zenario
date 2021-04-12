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
	zenarioGM
) {
	"use strict";
	


var formId = 'settings',
	linksId = 'download_links',
	closeButtonId = 'close_button';

zenarioGM.initVars();
zenarioGM.gridId = 'grid';
zenarioGM.mtPrefix = 'zenario_grid_maker_';
zenarioGM.addToolbarId = 'grid_add_toolbar';



zenarioGM.init = function(data, layoutId, layoutName, familyName) {
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
	
	zenarioGM.checkData(layoutName, familyName);
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





zenarioGM.editProperties = function(el) {
	var $el = $(el),
		cssClasses = ['zenario_grid_box', 'zenario_grid_rename_slot_box'],
		m = {
			type: $el.data('type'),
			name: $el.data('name'),
			small: $el.data('small'),
			height: $el.data('height'),
			is_full: $el.data('is_full'),
			is_last: $el.data('is_last'),
			css_class: $el.data('css_class'),
			html: $el.data('html'),
			grid_css_class: $el.data('grid_css_class')
		};
	
	$.colorbox({
		transition: 'none',
		html: zenarioGM.microTemplate(zenarioGM.mtPrefix + 'object_properties', m),
		
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
};




zenarioGM.ajaxURL = function() {
	return URLBasePath + 'zenario/admin/grid_maker/ajax.php';
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


zenarioGM.drawEditor = function(
	thisContId, thisCellId, 
	levels,
	gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
	gGutterNested,
	gColWidthPercent, gGutterWidthPercent
) {
	
	if (!levels) {
	    
		$('#' + formId + '--tabs').removeClass('zenario_grid_tabs_grid_selected').addClass('zenario_grid_tabs_slots_selected');
	}
	
	return methodsOf(zenarioG).drawEditor.call(this,
		thisContId, thisCellId, 
		levels,
		gCols, gColWidth, gGutter, gGutterLeftEdge, gGutterRightEdge,
		gGutterNested,
		gColWidthPercent, gGutterWidthPercent
	);
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







},
	window.zenarioGM = new zenarioG
);