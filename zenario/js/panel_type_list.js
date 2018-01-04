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
	panelTypes.list = extensionOf(panelTypes.grid)
);





methods.showPanel = function($header, $panel, $footer) {
	this.setHeader($header);
	this.showViewOptions($header);
	
	this.drawItems($panel);
	this.setupListViewColumns($panel);
	this.setScroll($panel);
	
	this.setTooltips($header, $panel, $footer);
	
	this.setupReordering($panel);
};


//Disable pagination if we're reordering items
methods.returnPageSize = function() {
	if (this.ordinalColumn()) {
		return false;
	} else {
		return methodsOf(panelTypes.grid).returnPageSize.call(this);
	}
};



methods.drawItems = function($panel) {
	this.items = this.getMergeFieldsForItemsAndColumns();
	$panel.html(this.microTemplate('zenario_organizer_list', this.items));
	$panel.show();
};


//Disable pagination if we're reordering items
methods.drawPagination = function($footer) {
	if (this.ordinalColumn()) {
		$footer.html(this.microTemplate('zenario_organizer_pagination', this.items)).show();
	} else {
		methodsOf(panelTypes.grid).drawPagination.apply(this, arguments);
	}
};







methods.returnInspectionViewEnabled = function() {
	return !!this.tuix.slidedown_view_microtemplate;
};

methods.openInspectionView = function(id) {
	
	var oldId = zenarioO.inspectionViewItemId();
	
	if (oldId) {
		this.closeInspectionView(oldId);
	}
	
	//Select the item we're opening inspection mode for
	zenarioO.selectItems(id);
	zenarioO.setButtons();
	
	var $toggle = $(get('organizer_slidedown_view_toggle_' + id)),
		$slide = $(get('organizer_slidedown_' + id));
	
	if (!$slide.is(':visible')) {
		$toggle
			.removeClass('organizer_slidedown_view_toggle_closed').addClass('organizer_slidedown_view_toggle_open');
		
		$slide
			.clearQueue()
			.removeClass('organizer_slidedown_view_closed').addClass('organizer_slidedown_view_open')
			.html(this.microTemplate(zenarioO.tuix.slidedown_view_microtemplate, {id: id}))
			.slideDown();
	}
	
	zenarioO.inInspectionView(id);
};

methods.closeInspectionView = function(id) {
	
	if (id === undefined) {
		id = zenarioO.inspectionViewItemId();
	}
	
	if (!id) {
		return;
	}
	
	var $toggle = $(get('organizer_slidedown_view_toggle_' + id)),
		$slide = $(get('organizer_slidedown_' + id));
		
	if ($slide.is(':visible')) {
		$toggle
			.removeClass('organizer_slidedown_view_toggle_open').addClass('organizer_slidedown_view_toggle_closed');
		
		$slide
			.clearQueue()
			.slideUp(400, function() {
				$slide
					.removeClass('organizer_slidedown_view_open').addClass('organizer_slidedown_view_closed')
					.html('');
			});
	}
	
	zenarioO.inInspectionView(false);
};





methods.addExtraMergeFieldsForColumns = function(data, column) {
	
	var c = column.id,
		prefs = zenarioO.prefs[this.path] || {},
		columnWidth;
	
	if (prefs.colSizes
	 && prefs.colSizes[c]) {
		columnWidth = Math.max(prefs.colSizes[c], zenarioO.columnWidths.xxsmall);
	} else {
		columnWidth = this.tuix.columns[c].width;
	}
	
	if (columnWidth && zenarioO.columnWidths[columnWidth]) {
		columnWidth = zenarioO.columnWidths[columnWidth];
	
	} else if (!(columnWidth = 1*columnWidth)) {
		columnWidth = zenarioO.defaultColumnWidth
	}
	
	if (data.totalWidth) {
		data.totalWidth += zenarioO.columnSpacing;
	}
	data.totalWidth += columnWidth + zenarioO.columnPadding;
	
	column.width = columnWidth;
};



methods.addExtraMergeFieldsForCells = function(data, column, row, cell) {
	cell.width = column.width;
};

//Add a flag if we are using the taller rows
methods.addExtraMergeFields = function(data) {
	if (engToBoolean(this.tuix.use_tall_rows)) {
		data.useTallRows = true;
	}
};


//This function makes all of the columns in list view the correct size,
//and adds logic for resizing/reordering them
methods.setupListViewColumns = function($panel) {
	
	//Attempt to always keep the headers at the top of the page
	var $organizer_header = $panel.find('.organizer_header');
	if ($organizer_header.length) {
		$panel.scroll(function() {
			$organizer_header.css('top', $panel.scrollTop());
		});
	}

	var that = this,
		$sortAndResizeWrapper = $panel.find('.organizer_sort_and_resize_wrapper'),
		$sortables = $panel.find('.organizer_sort_and_resize_wrapper .organizer_sortable'),
		$resizables = $panel.find('.organizer_sort_and_resize_wrapper .organizer_resizable'),
		sortAndResizeable$Cols = {},
		sortableColOrigins = {},
		sizeListViewColumns = function() {
			var thisWidth,
				width = $('#organizer_non_sortable_resizeable_cols').width(),
				widthWithExtraMarginForZoomErrors;
			
			$resizables.each(function(i, el) {
				if (thisWidth = $(el).outerWidth()) {
					width += thisWidth;
				}
			});
			
			//Some hacks to try and stop things that are supposed to be on one line wrapping on various browsers/browser zoom levels
			//#organizer_list_view has the width we want, with overflow-x: hidden;
			//#organizer_list_view .organizer_row has an extra 20 pixels of leeway, which should hopefully be hidden by the above
			widthWithExtraMarginForZoomErrors = width;
			if (window.outerWidth != window.innerWidth) {
				widthWithExtraMarginForZoomErrors += 20;
			} else {
				widthWithExtraMarginForZoomErrors += 5;
			}
			$('#organizer_list_view .organizer_row').css('min-width', widthWithExtraMarginForZoomErrors + 'px');
		},
		sortHandler = function(event, ui) {
			that.saveScrollPosition($panel);
			
			$sortables.each(function(i, el) {
				var id = el.id;
				if (id) {
					if (!sortAndResizeable$Cols[id]) {
						sortAndResizeable$Cols[id] = $('.' + id + '__cell');
					}
					
					var left = 1*$(el).position().left - sortableColOrigins[id];
					sortAndResizeable$Cols[id].css('left', left + 'px');
				}
			});
			
			that.restoreScrollPosition($panel);
		},
		resizeHandler = function(event, ui, save) {
			that.saveScrollPosition($panel);
			
			var id = $(ui.element).attr('id');
			if (id) {
				if (!sortAndResizeable$Cols[id]) {
					sortAndResizeable$Cols[id] = $('.' + id + '__cell');
				}
				
				if (save) {
					zenarioO.resizeColumn(id.replace('organizer_column__', ''), ui.element.width());
				} else {
					$sortAndResizeWrapper.css('min-width', 'auto');
					$sortAndResizeWrapper.css('max-width', 'auto');
					sortAndResizeable$Cols[id].width(ui.element.width());
					sizeListViewColumns();
				}
			}
			
			that.restoreScrollPosition($panel);
		};
	
	$resizables.resizable({
		handles: 'e',
		minWidth: zenarioO.columnWidths.xxsmall,
		start: function(event, ui) {
			zenarioO.resizingColumn = true;
		},
		resize: resizeHandler,
		stop: function(event, ui) {
			resizeHandler(event, ui, true);
			setTimeout(
				function() {
					delete zenarioO.resizingColumn;
				}, 50
			);
		}
	});
	
	$sortAndResizeWrapper.sortable({
		containment: 'parent',
		items: '.organizer_sortable',
		
		start: function(event, ui) {
			zenarioO.sortingColumn = true;
		},
		sort: sortHandler,
		stop: function(event, ui) {
			that.saveScrollPosition($panel);
			
			sortHandler(event, ui);
			setTimeout(
				function() {
					delete zenarioO.sortingColumn;
				}, 50
			);
			
			var newOrder = $sortAndResizeWrapper.sortable('toArray');
			
			foreach (newOrder as var n) {
				newOrder[n] = newOrder[n].replace('organizer_column__', '');
			}
			
			zenarioO.switchColumnOrder(newOrder);
			
			that.restoreScrollPosition($panel);
		}
	});
	
	sizeListViewColumns();
	
	$sortables.each(function(i, el) {
		if (el.id) {
			sortableColOrigins[el.id] = 1*$(el).position().left;
		}
	});
};

methods.setupReordering = function($panel) {
	var that = this,
		ordCol = that.ordinalColumn();
	
	//Start reordering running, if it is enabled on this panel, and we're not currently searching
	if (ordCol && this.searchTerm === undefined) {
		$panel
			.find('#organizer_items_wrapper')
			.sortable({
				opacity: 0.8,
				cancel: '.organizer_slidedown_view,input,textarea,button,select,option',
				start: function() {
					that.closeInspectionView();
					zenarioO.disableInteraction();
				},
				stop: function (sorted, ui) {
					zenarioO.enableInteraction();
					
					//Handle the results of a reorder:
					//Get a list of item ids in the new order
					var i,
						itemNo,
						saves = '',
						items = {},
						values = [],
						oldOrder = [],
						newOrder = $panel
							.find('#organizer_items_wrapper')
							.sortable('toArray', {attribute: 'data-id'});
				
					//Remove any bad data; e.g. slide-down view <div>s
					for (i = newOrder.length; i >= 0; --i) {
						if (newOrder[i] === ''
						 || newOrder[i] === undefined) {
							newOrder.splice(i, 1);
						}
					}
				
					//Create an array of which items we can see
					foreach (newOrder as itemNo => i) {
						items[i] = true;
					}
				
					//Look through the searchedItems array and get their old order, and their value from the items array
					foreach (zenarioO.searchedItems as itemNo => i) {
						if (items[i]) {
							oldOrder.push(i);
							values.push(that.tuix.items[i][ordCol]);
						}
					}
				
					//Look through newOrder and oldOrder for any changes, and update those column
					var actionRequests = zenarioO.getKey();
					actionRequests.ordinals = {};
					foreach (newOrder as itemNo => i) {
						if (i != oldOrder[itemNo]) {
							saves += (saves? ',' : '') + i;
							actionRequests.ordinals[i] = values[itemNo];
						}
					}
					actionRequests.reorder = true;
					actionRequests.id = saves;
					
					// Get ID of dropped item
					actionRequests.dropped_item = $(ui.item).data('id');
					
					//Send these results via AJAX
					var actionTarget =
						'zenario/ajax.php?' +
							'__pluginClassName__=' + that.tuix.reorder.class_name +
							'&__path__=' + zenarioO.path +
							'&method_call=handleOrganizerPanelAJAX';
				
					//Clear the local storage, as there have probably just been changes
					delete zenario.rev;
				
					$.post(
						URLBasePath + actionTarget,
						actionRequests,
						//Refresh the panel to show the new order
						function () {
							zenarioO.reload();
						}
					);
				}
			});
	}
};

//Is reordering enabled, and if so, which column is being used
methods.ordinalColumn = function() {
	return !window.zenarioOSelectMode && this.tuix.reorder && this.tuix.reorder.column;
};







}, zenarioO.panelTypes);