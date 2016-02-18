/*
 * Copyright (c) 2016, Tribal Limited
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
	zenario, zenarioA, zenarioAB, zenarioAT, zenarioO,
	get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
	extensionOf, methodsOf,
	panelTypes
) {
	"use strict";





//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.grid = extensionOf(panelTypes.base)
);






methods.returnPanelTitle = function() {
	var title = this.tuix.title;
	
	if (window.zenarioOSelectMode && (this.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)) {
		if (window.zenarioOMultipleSelect && this.tuix.multiple_select_mode_title) {
			title = this.tuix.multiple_select_mode_title;
		} else if (this.tuix.select_mode_title) {
			title = this.tuix.select_mode_title;
		}
	}
	
	if (zenarioO.filteredView) {
		title += phrase.refined;
	}
	
	return title;
};

//n.b. your showPanel() method is special; it will be called by the CMS when the panel needs to be drawn
methods.showPanel = function($header, $panel, $footer) {
	this.setHeader($header);
	this.showViewOptions($header);
	
	this.drawItems($panel);
	this.setScroll($panel);
	
	this.setTooltips($header, $panel, $footer);
};

methods.drawItems = function($panel) {
	this.items = this.getMergeFieldsForItemsAndColumns(true);
	$panel.html(zenarioA.microTemplate('zenario_organizer_grid', this.items));
	$panel.show();
};

methods.showViewOptions = function($header) {
	//Show the view "options" button
	$header.find('#organizer_viewOptions').show();
};

//n.b. your showButtons() method is special; it will be called by the CMS when the buttons needs to be drawn
methods.showButtons = function($buttons) {
	var buttons, html,
		m = {
			itemButtons: false,
			collectionButtons: false
		};
	
	//If there is at least one item selected, show the item buttons, otherwise show the collection buttons.
	//Never show both the item buttons and the collection buttons at the same time
	if (zenarioO.itemsSelected > 0) {
		m.itemButtons = zenarioO.getItemButtons();
	} else {
		m.collectionButtons = zenarioO.getCollectionButtons();
	}
	
	html = zenarioA.microTemplate('zenario_organizer_panel_buttons', m);
	
	//if (html.replace(/\s+/g, '') != $buttons.html().replace(/\s+/g, '')) {
		$buttons.html(html).show();
	//}
	
	this.enableDragDropUpload(m.collectionButtons, m.itemButtons);

	zenarioA.tooltips($buttons.find('a[title]'));
	zenarioA.tooltips($buttons.find('.toolbarButtons ul ul a[title]'), {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});

};

methods.setScroll = function($panel) {
	methods.restoreScrollPosition($panel);
	
	//If there's an item selected, attempt to find it's element on the page,
	//get where it is compared to its parent, and then scroll to it
	var item, position, scrollTo;
	if (item = zenarioO.getKeyId(true)) {
		//I'm having to use setTimeout() here because the position() and offset() functions
		//Don't seem to work properly if they are called in the same thread :(
		setTimeout(function() {
			if (position = $(get('organizer_item_' + item)).position()) {
				scrollTo = Math.max(0, position.top + $panel.scrollTop() - Math.floor($panel.height() / 4));
			
				//$panel.scrollTop(scrollTo);
				$panel.stop().animate({scrollTop: scrollTo}, 250);
			}
		}, 1);
	}
};


//Set a drag/drop upload if possible
methods.enableDragDropUpload = function(collectionButtons, itemButtons) {
	
	if (!zenarioA.canDoHTML5Upload()) {
		this.disableDragDropUpload();
	}
	
	var i, id, button,
		uploadButton = false,
		uploadIsItemLevel = false;
	
	//Look to see if there is an upload button available, and break when we find one
	do {
		//First look through the merge fields for the item buttons that are being shown,
		//looking to see if any are upload buttons
		if (itemButtons) {
			foreach (itemButtons as i => button) {
				if (!button.disabled
				 && button.tuix.upload
				 && engToBoolean(button.tuix.upload.drag_and_drop)) {
					uploadIsItemLevel = true;
					uploadButton = button.tuix;
					break;
				}
			}
		}
		
		//First look through the merge fields for the collection buttons that are being shown,
		//looking to see if any are upload buttons
		if (collectionButtons) {
			foreach (collectionButtons as i => button) {
				if (!button.disabled
				 && button.tuix.upload
				 && engToBoolean(button.tuix.upload.drag_and_drop)) {
					uploadButton = button.tuix;
					break;
				}
			}
		
		//If we weren't passed the merge fields for the collection buttons,
		//look through the TUIX definitions instead. (This is slightly slower than
		//above as we need to calculate the rules for hidden/disabled again.)
		} else {
			foreach (this.tuix.collection_buttons as id => button) {
				if (button
				 && button.upload
				 && engToBoolean(button.upload.drag_and_drop)
				 && !zenarioO.checkButtonHidden(button)
				 && !zenarioO.checkDisabled(button)) {
					uploadButton = button;
					break;
				}
			}	
		}
	
	} while (false);
	
	
	
	if (uploadButton) {
		var k, request = zenarioO.getKey(zenarioO.uploadIsItemLevel);
		
		if (uploadButton.upload
		 && uploadButton.upload.request) {
			foreach (uploadButton.upload.request as k) {
				request[k] = uploadButton.upload.request[k];
			}
		}
		
		request.__pluginClassName__ = uploadButton.class_name;
		request.__path__ = this.path;
		request.method_call = 'handleOrganizerPanelAJAX';
		
		zenarioA.setHTML5UploadFromDragDrop(
			URLBasePath + 'zenario/ajax.php',
			request,
			function() {
				zenarioO.disableInteraction();
			},
			function() {
				zenarioO.enableInteraction();
				zenarioO.selectCreatedIds();
			},
			get('organizer_rightColumn')
		);
		
		$('#organizer_rightColumn').addClass('upload_enabled').removeClass('dragover');
	
	} else {
		this.disableDragDropUpload();
	}
};

methods.disableDragDropUpload = function() {
	zenarioA.clearHTML5UploadFromDragDrop();
	$('#organizer_rightColumn').removeClass('upload_enabled').removeClass('dragover');
};








methods.setHeader = function($header) {
	var m = {
		quickFilters: zenarioO.getQuickFilters()
	};
	$header.html(zenarioA.microTemplate('zenario_organizer_panel_header', m));
	$header.show();
};

methods.sizePanel = function($header, $panel, $footer, $buttons) {
	if (this.items) {
		this.drawPagination($footer);
	}
};


//Draw some pagination
methods.drawPagination = function($footer) {
	
	$footer.html(zenarioA.microTemplate('zenario_organizer_pagination', this.items)).show();
	
	var pageCount = zenarioO.getPageCount(),
		$pagination = $footer.find('#organizer_pagination');
	
	//Check that there are multiple pages, and that the html for the pagination is on the page
	if (pageCount > 1
	 && $pagination.size()) {
		
		//This setTimeout is to fix a bug that sometimes occurs in Firefox
		setTimeout(function() {
			
			if($( window ).width() <= 890) {
				var numberPaginationPages = 5;
			} else {
				var numberPaginationPages = 10;
			}
			
			//Call the jPaginator jQuery plugin to set up some page buttons
			$pagination.jPaginator({ 
				
				nbPages: zenarioO.getPageCount(), 
				selectedPage: zenarioO.getCurrentPage(),
				
				nbVisible: numberPaginationPages,
				//widthPx: Math.max(20, 10 * (1 + Math.ceil(Math.log10(zenarioO.getPageCount())))),
				widthPx: 24,
				marginPx: 1,
		
				overBtnLeft:'#organizer_page_left', 
				overBtnRight:'#organizer_page_right', 
		
				withSlider: true,
				minSlidesForSlider: 2,
			
				withAcceleration: true,
				speed: 2,
				coeffAcceleration: 2,
		
		
				onPageClicked: function($pageButton, pageNum) { 
					zenarioO.goToPage(pageNum);
				}
			});
		}, 0);
	}
};



methods.setTooltips = function($header, $panel, $footer) {
	zenarioA.tooltips($panel.find('[title]'));
	zenarioA.tooltips($header.find('a[title]'));
	zenarioA.tooltips($header.find('#organizer_quickFilter ul ul'), {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
};






//This function looks through the data sent via AJAX (which is stored in the this.tuix variable)
//and translates it into merge fields for list view and grid view.
//It was originally part of the zenarioO library of functions, and while it has now been moved into the code
//for panel types, there are still some references to some static variables from the zenarioO library.
methods.getMergeFieldsForItemsAndColumns = function(useLargerThumbnails) {
	
	var itemsExist = false,
		itemButtonsExist = false,
		c, column, colNo, row, cell,
		bi,
		ci = -1,
		ii = -1,
		lastCol = false,
		firstCell = 'firstcell ',
		numberOfInlineButtons = 0,
		labelColumns = {},
		labelFormat, boldColsInListView,
		data = {
			items: [],
			columns: [],
			totalWidth: 0,
			canClickThrough: false,
			maxNumberOfInlineButtons: 0,
			allItemsSelected: zenarioO.allItemsSelected()
		};
		
	//Set the format for labels
	if (!(labelFormat = this.tuix.label_format_for_grid_view)) {
		labelFormat = '[[' + zenarioO.defaultSortColumn + ']]';
	}
	boldColsInListView = this.tuix.bold_columns_in_list_view || this.tuix.label_format_for_picked_items || labelFormat;
	zenarioO.popoutLabelFormat = this.tuix.label_format_for_popouts;

		
	
	
	if (this.tuix.item_buttons) {
		foreach (this.tuix.item_buttons as bi) {
			itemButtonsExist = true;
			break;
		}
	}
	
	foreach (zenarioO.sortedColumns as colNo => c) {
		if (zenarioO.isShowableColumn(c, true)) {
			lastCol = c;
		}
	}
	foreach (zenarioO.sortedColumns as colNo => c) {
		
		if (boldColsInListView == c
		 || boldColsInListView.indexOf('[[' + c + ']]') !== -1) {
			labelColumns[c] = true;
		}
		
		if (zenarioO.isShowableColumn(c, true)) {
			
			column = {
				id: c,
				tuix: this.tuix.columns[c],
				htmlId: 'organizer_column__' + c,
				css_class:
					firstCell + (lastCol == c? 'lastcell' : '') +
					(labelColumns[c]? ' label_column' : '') +
					zenarioO.columnCssClass(c),
				title: this.tuix.columns[c].title,
				tooltip: this.tuix.columns[c].tooltip
			};
			
			this.addExtraMergeFieldsForColumns(data, column);
			data.columns[++ci] = column;
			firstCell = '';
		}
	}
	data.totalWidth += zenarioO.columnsExtraSpacing;
	
	zenarioO.shownItems = {};
	zenarioO.shownItemsLength = 0;
	if (this.tuix.items) {
		//Work out which items to display for this page,
		var pageStop, pageStart;
		if (zenarioO.thisPageSize) {
			pageStop = zenarioO.page * zenarioO.thisPageSize,
			pageStart = pageStop - zenarioO.thisPageSize;
			pageStop = Math.min(pageStop, zenarioO.searchMatches);
			
			data.pageStart = pageStart + 1;
			data.pageStop = pageStop;
		}
		data.page = zenarioO.page;
		data.pageCount = zenarioO.pageCount;
		data.itemCount = zenarioO.searchMatches;
		
		var canClickThrough,
			firstRow = 'firstrow ';
		
		foreach (zenarioO.searchedItems as var itemNo => var i) {
			itemsExist = true;
			
			if (zenarioO.thisPageSize) {
				if (itemNo >= pageStop) {
					break;
				} else if (itemNo < pageStart) {
					continue;
				}
			}
			
			if (!this.tuix.items[i]) {
				continue;
			}
			
			if (canClickThrough = !!zenarioO.itemClickThroughLink(i)) {
				data.canClickThrough = true;
			}
			
			row = {
				id: i,	//Using "[[id]]" in your microtemplates should both html and js escape this
				cells: [],
				tuix: this.tuix.items[i],
				open_in_inspection_view: zenarioO.inspectionView && i == zenarioO.inspectionViewItem,
				canClickThrough: canClickThrough,
				showCheckbox: window.zenarioOSelectMode || itemButtonsExist,
				//canDrag: zenarioO.changingHierarchyView && !engToBoolean(this.tuix.items[i].disable_reorder),
				label: zenarioO.applySmallSpaces($.trim(zenarioO.applyMergeFields(labelFormat, false, i, true))).replace(/\n/g, '<br/>'),
				selected: !!this.selectedItems[i],
				css_class: zenarioO.rowCssClass(i) + ' ' + firstRow
			};
			
			row.image_css_class = zenarioO.getItemCSSClass(i);
			
			if (!useLargerThumbnails && this.tuix.items[i].list_image) {
				row.image_css_class += ' organiser_item_with_image';
				row.image = zenario.addBasePath(this.tuix.items[i].list_image);
			
			} else if (useLargerThumbnails && this.tuix.items[i].image) {
				row.image_css_class += ' organiser_item_with_image';
				row.image = zenario.addBasePath(this.tuix.items[i].image);
			}
			
			row.tooltip = this.tuix.items[i].tooltip;
			if (!row.tooltip && this.tuix.item) {
				if (this.tuix.item.tooltip_when_link_is_active && zenarioO.itemClickThroughLink(i)) {
					row.tooltip = zenarioO.applyMergeFields(this.tuix.item.tooltip_when_link_is_active, true, i);
				} else if (this.tuix.item.tooltip) {
					row.tooltip = zenarioO.applyMergeFields(this.tuix.item.tooltip, true, i);
				}
			}
			
			
			row.inline_buttons = zenarioO.getInlineButtons(i);
			
			if (row.inline_buttons
			 && (numberOfInlineButtons = row.inline_buttons.length)
			 && (numberOfInlineButtons > data.maxNumberOfInlineButtons)) {
				data.maxNumberOfInlineButtons = numberOfInlineButtons;
			}
			
			this.addExtraMergeFieldsForRows(data, row);
			
			var ei = -1,
				firstCell = 'firstcell ',
				needsComma = false,
				lastNeedsComma = false,
				value;
			
			ci = -1;
			foreach (zenarioO.sortedColumns as colNo => c) {
				if (zenarioO.isShowableColumn(c, true)) {
					column = data.columns[++ci];
					
					value = zenarioO.columnValue(i, c);
					
					//Put commas between words, but don't put commas between non-words or words that end with something.
					needsComma = !(value == '' && value !== 0);
					
					cell = {
						id: c,
						first: !!firstCell,
						css_class:
							'organizer_column__' + c + '__cell ' +
							firstRow + firstCell + (lastCol == c? 'lastcell' : '') +
							(engToBoolean(this.tuix.columns[c].align_right)? ' right' : '') +
							(labelColumns[c]? ' label_column' : '') + 
							zenarioO.columnCssClass(c, i),
						value: value,
						needsComma: needsComma && lastNeedsComma
					};
					
					this.addExtraMergeFieldsForCells(data, column, row, cell);
					
					row.cells[++ei] = cell;
					firstCell = '';
					lastNeedsComma = needsComma && !(value + '').match(/\W\s*$/);
				}
			}
			
			data.items[++ii] = row;
			zenarioO.shownItems[i] = true;
			++zenarioO.shownItemsLength;
			firstRow = '';
		}
		
		foreach (data.items as ii => row) {
			row.maxNumberOfInlineButtons = data.maxNumberOfInlineButtons;
		}
	}
	
	//Remove any items that have disappeared from view
	foreach (this.selectedItems as var i) {
		if (!zenarioO.shownItems[i]) {
			delete this.selectedItems[i];
		}
	}
	
	
	if (!itemsExist) {
		//Display a message if there were no items to display
		if (zenarioO.filteredView) {
			data.no_items_message = this.tuix.no_items_in_search_message? this.tuix.no_items_in_search_message : phrase.noItemsInSearch;
		} else {
			data.no_items_message = this.tuix.no_items_message? this.tuix.no_items_message : phrase.noItems;
		}
	}
	
	this.addExtraMergeFields(data);
	
	return data;
};


methods.addExtraMergeFields = function(data) {
	//...
};

methods.addExtraMergeFieldsForColumns = function(data, column) {
	//...
};

methods.addExtraMergeFieldsForRows = function(data, row) {
	//...
};

methods.addExtraMergeFieldsForCells = function(data, column, row, cell) {
	//...
};





}, zenarioO.panelTypes);