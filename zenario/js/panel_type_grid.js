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
		3. It may be wrapped togther with other files (this is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and inc-organizer.js.php for step (3).
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
	
	if (window.zenarioOSelectMode && (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)) {
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
	
	//Show the view "options" button if in grid view
	$header.find('#organizer_viewOptions').show();
	
	this.items = zenarioO.getPanelItems(true);
	$panel.html(zenarioA.microTemplate('zenario_organizer_grid', this.items));
	$panel.show();
	this.setScroll($panel);
	
	this.drawPagination($footer, this.items);
	this.setTooltips($header, $panel, $footer);
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
				if (!zenarioO.isInfoTag(id)
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
				if (!zenarioO.isInfoTag(k)) {
					request[k] = uploadButton.upload.request[k];
				}
			}
		}
		
		request.__pluginClassName__ = uploadButton.class_name;
		request.__path__ = zenarioO.path;
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







}, zenarioO.panelTypes);