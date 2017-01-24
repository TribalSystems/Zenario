/*
 * Copyright (c) 2017, Tribal Limited
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
	extensionOf, methodsOf, has
) {
	"use strict";




//To do: Functions to document in the API directory in javascript_organizer.js:
/*
	//getting arrays of merge fields
	zenarioO.getPanelItems(useLargerThumbnails)
	zenarioO.getItemButtons()
	zenarioO.getCollectionButtons()

	zenarioO.disableInteraction()
	zenarioO.enableInteraction()

	zenarioO.load()
	zenarioO.reload(
		//Need to tidy up inputs
	)

	zenarioO.setHash()
	zenarioO.setPanel()
		//includes zenarioO.setTrash()
	zenarioO.setButtons()
		//includes zenarioO.setChooseButton()
	zenarioO.setBackButton()
	zenarioO.setNavigation()
	zenarioO.setPanelTitle()
	zenarioO.getCurrentPage()
	zenarioO.getPageCount()


	zenarioO.pi.itemClick(
		//Need to tidy up inputs
	)
	zenarioO.itemClickThrough(
		//Need to tidy up inputs
	)

	zenarioO.toggleAllItems()
	zenarioO.selectItems()
	
	//inspection view functions
	zenarioO.inspectionViewEnabled()
	zenarioO.inInspectionView(stateChange)
	zenarioO.inspectionViewItemId(stateChange)
	zenarioO.openInspectionView()
	zenarioO.closeInspectionView()
	zenarioO.toggleInspectionView()
*/

/*
	//Things that might become API functions, that I need to review:

	zenarioO.path
	zenarioO.getKeyId()
	zenarioO.itemsSelected
	zenarioO.selectCreatedIds()
	zenarioO.saveSelection()
	zenarioO.searchedItems

	zenarioO.checkButtonHidden()
	zenarioO.checkDisabled()

	//list view stuff
	zenarioO.resizeColumn()
	zenarioO.columnWidths
	zenarioO.resizingColumn
	zenarioO.sortingColumn
	zenarioO.switchColumnOrder()
*/



//Constants
zenarioO.getItemLinkTimeoutTime = 7000;

//(Periodic refresh is now disabled)
//zenarioO.periodicRefreshTime = 120000;

zenarioO.yourWorkInProgressLastUpdateFrequency = 60000;
zenarioO.yourWorkInProgressItemCount = 10;

zenarioO.itemDoubleClickTime = 500;
zenarioO.searchDelayTime = 700;
zenarioO.firstLoaded = false;

zenarioO.defaultPageSize = 50;
zenarioO.defaultColumnWidth = 150;
zenarioO.columnsExtraSpacing = 1;
zenarioO.columnSpacing = 1;
zenarioO.columnPadding = 10;
zenarioO.columnWidths = {
	xxsmall: 45,
	xsmall: 75,
	small: 100,
	medium: 150,
	large: 225,
	xlarge: 350,
	xxlarge: 500
};
zenarioO.pi = false;
zenarioO.inspectionView = false;




//Create an instance of the admin forms library for the view options box
var zenarioVO = window.zenarioVO = new zenarioAF();
zenarioVO.init('zenarioVO', 'zenario_filters');




//Go to the "Content by Layout" panel by default
zenarioO.defaultPath = 'zenario__content/panels/content';
zenarioO.defaultPathInIframePreload = 'dummy_item/panels/loading_message';



zenarioO.inInspectionView = function(stateChange) {
	if (!zenarioO.inspectionViewEnabled()) {
		zenarioO.inspectionView = false;
		return false;
	}
	
	if (stateChange === undefined) {
		return zenarioO.inspectionView;
	
	} else if (stateChange === false) {
		zenarioO.inspectionView =
		zenarioO.inspectionViewItem = false;
		zenarioO.setHash();
	
	} else {
		zenarioO.inspectionView = true;
		zenarioO.inspectionViewItem = stateChange;
		zenarioO.setHash();
	}
};

zenarioO.inspectionViewItemId = function() {
	if (zenarioO.inInspectionView()) {
		return zenarioO.inspectionViewItem;
	}
	return false;
};

zenarioO.inspectionViewEnabled = function() {
	return zenarioO.pi
		&& zenarioO.pi.returnInspectionViewEnabled();
};

zenarioO.toggleInspectionView = function(id) {
	if (zenarioO.inspectionViewEnabled()) {
		zenarioO.pi.toggleInspectionView(id);
	}
};

zenarioO.openInspectionView = function(id) {
	if (zenarioO.inspectionViewEnabled()) {
		zenarioO.pi.openInspectionView(id);
	}
};

zenarioO.closeInspectionView = function(id) {
	if (zenarioO.inspectionViewEnabled()) {
		zenarioO.pi.closeInspectionView(id);
	}
};


zenarioO.print = function() {
	try {
		
		var title = $("#organizer_panelTitle div:eq(1)").text();
		var table = $('.organizer_list_view').removeAttr('style').html();
		
		//table.$('.organizer_column').removeAttr('style');
		
		var mywindow = window.open('', title, 'height=50,width=50');
        mywindow.document.write('<html><head><title></title>');
        mywindow.document.write('<link rel="stylesheet" href="http://dev-jc.tribiq.com/HEAD/zenario/styles/admin_organizer_print.css" type="text/css" />');
        //mywindow.document.write('<link rel="stylesheet" media="screen" href="http://dev-jc.tribiq.com/HEAD/zenario/styles/admin_organizer_print.css" type="text/css" />');
        mywindow.document.write('<script>myFunction = function() { window.print(); window.close();};</script>');

        mywindow.document.write('</head><body onload="myFunction()"><div class="table_wrapper"><h1 class="print_title">' + title + '</h1>');
        mywindow.document.write(table);
        
        mywindow.document.write('</div></body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10
		
		//mywindow.print();
		//mywindow.close(); 
        	
		//window.print();
		
	} catch(e) {
	}
};




zenarioO.open = function(className, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	if (!zenarioO.topLeftHTML) {
		zenarioO.topLeftHTML = '';
	}
	if (!zenarioO.topRightHTML) {
		zenarioO.topRightHTML =
			'<div class="organizer_close_button">' +
				'<a onclick="zenarioO.closeSelectMode();"></a>' +
			'</div>';
	}
	
	var html = zenarioA.microTemplate('zenario_organizer', {topLeftHTML: zenarioO.topLeftHTML, topRightHTML: zenarioO.topRightHTML});
	
	zenarioA.openBox(html, className, 'og', e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement);
};



//Start up the JavaScript engine for the Organizer as soon as the page has loaded
zenarioO.initRun = false;
zenarioO.init = function(reload) {
	
	//Unless the reload flag is set, don't reload if we've already done this
	if (zenarioO.initRun && !reload) {
		return;
	}
	zenarioO.initRun = true;
	
	zenarioA.showAJAXLoader();
	
	//if (window.console && typeof console.groupEnd == 'function') console.group('Loading Organizer');
	
	zenarioO.pageTitle = document.title;
	
	//Set the search box to the default search Phrase
	zenarioO.setSearch(undefined);
	
	//Load the current admin's Organizer preferences
	zenarioO.checkPrefs();
	
	
	zenarioO.loadMap(zenarioO.init2);
};

zenarioO.loadMap = function(after) {
	
	//Check if the map was already loaded!
	if (zenarioO.map) {
		after();
		return;
	}
	
	//Load the map
	try {
		//If this is a select mode window launched from Organizer, try to copy the map off of the launching Organizer
		if (	   zenarioA.openedInIframe
				&& self.parent
				&& self.parent.zenarioO
				&& self.parent.zenarioO.map
				&& self.parent.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& self.parent.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = self.parent.zenarioO.map;
			zenarioO.lookForBranches(zenarioO.map);
			after();
			return;
		
		//Check if this window had an opener, which already had Organizer loaded
		} else if (windowOpener
				&& windowOpener.zenarioO
				&& windowOpener.zenarioO.map
				&& windowOpener.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& windowOpener.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = windowOpener.zenarioO.map;
			zenarioO.lookForBranches(zenarioO.map);
			after();
			return;
		
		//Check if this window had an windowOpener, which already had Organizer preloaded
		} else if (windowOpener
				&& windowOpener.zenario
				&& windowOpener.zenario.get
				&& windowOpener.zenario.get('zenario_sk_iframe')
				&& windowOpener.zenario.get('zenario_sk_iframe').contentWindow
				&& windowOpener.zenario.get('zenario_sk_iframe').contentWindow.zenarioO
				&& windowOpener.zenario.get('zenario_sk_iframe').contentWindow.zenarioO.map
				&& windowOpener.zenario.get('zenario_sk_iframe').contentWindow.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& windowOpener.zenario.get('zenario_sk_iframe').contentWindow.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = windowOpener.get('zenario_sk_iframe').contentWindow.zenarioO.map;
			zenarioO.lookForBranches(zenarioO.map);
			after();
			return;
		}
	} catch (e) {
		//Run the code below if something went wrong
	}
	
	
	var store, url = URLBasePath + 'zenario/admin/organizer.ajax.php';
	
	//Attempt to get the map from the local store
	//if (zenarioO.map = zenario.checkSessionStorage(url, {}, true)) {
	//	after();
	//	zenarioO.lookForBranches(zenarioO.map);
	//
	//} else {
	//	//Otherwise launch an AJAX request to get the map
	//	zenarioA.keepTrying(function(attempt) {
	//		$.ajax({
	//			url: url,
	//			dataType: 'text'
	//		}).fail(function(resp, statusType, statusText) {
	//			if (zenarioA.stopTrying(attempt)) {
	//				zenarioA.AJAXErrorHandler(resp, statusType, statusText);
	//			}
	//			
	//		}).done(function(data) {
	//			if (zenarioA.stopTrying(attempt)) {
	//				if (zenarioO.map = zenarioA.readData(data, url, {})) {
	//					after();
	//					zenarioO.lookForBranches(zenarioO.map);
	//				}
	//			}
	//		
	//		});
	//	});
	//}
	
	zenario.ajax(url, false, false, true).after(function (data) {
		if (zenarioO.map = zenarioA.readData(data, url, {})) {
			zenarioO.lookForBranches(zenarioO.map);
			after();
		}
	});
};


zenarioO.init2 = function() {
	
	zenarioA.tooltips();
	
	
	zenarioO.sortedTopLevelItems = zenarioO.getSortedIdsOfTUIXElements(zenarioO.map);
	zenarioO.setNavigation();
	zenarioO.setTopRightButtons();
	
	if (zenarioA.canDoHTML5Upload()) {
		get('organizer_rightColumn').addEventListener('dragover', function() {
			$('#organizer_rightColumn').addClass('dragover');
		}, false);
		get('organizer_rightColumn').addEventListener('dragleave', function() {
			$('#organizer_rightColumn').removeClass('dragover');
		}, false);
	}
	
	if (zenarioO.checkQueue()) {
		//if (window.console && typeof console.groupEnd == 'function') console.groupEnd();
		return;
	
	} else if (!zenarioA.openedInIframe) {
		zenarioO.go();
		//if (window.console && typeof console.groupEnd == 'function') console.groupEnd();
	}
};



zenarioO.prefs = {};
zenarioO.prefsChecksum = '{}';
zenarioO.previousPrefChecksums = {'{}': true};

//Update the current admin's Organizer preferences from the server to the client if needed.
zenarioO.checkPrefs = function() {
	
	//Check a checksum of the prefs as stored on the server.
	var prefs,
		url = URLBasePath + 'zenario/admin/quick_ajax.php?_manage_prefs=1',
		serverChecksum = zenario.nonAsyncAJAX(url + '&_get_checksum=1', true, false);
	
	//If this doesn't match the checksum on the client attempt to load in the new value.
	//(If there wasn't anything on the server, if it was corrupt, or we know that
	//the server's was an old copy, then keep the client's current copy.)
	if (serverChecksum && serverChecksum != zenarioO.prefsChecksum && !zenarioO.previousPrefChecksums[serverChecksum]) {
		if ((prefs = zenario.nonAsyncAJAX(url + '&_load_prefs=1', true, true)) && (prefs)) {
			zenarioO.prefs = prefs;
			zenarioO.previousPrefChecksums[zenarioO.prefsChecksum = serverChecksum] = true;
			return false;
		}
	}
	
	return true;
};

//Save the current admin's Organizer preferences from the client to the server.
zenarioO.savePrefs = function(sync) {
	
	var request = {
		_manage_prefs: 1,
		_save_prefs: 1,
		prefs: JSON.stringify(zenarioO.prefs)};
	
	//We'll generate a new checksum so that others can tell that this is a change.
	//We'll also remember the checksum to try and prevent accidentally loading an old checksum due to any race conditions.
	zenarioO.previousPrefChecksums[zenarioO.prefsChecksum = request.checksum = hex_md5(request.prefs)] = true;
	
	$.ajax({
		type: 'POST',
		async: !sync,
		dataType: 'text',
		url: URLBasePath + 'zenario/admin/quick_ajax.php',
		data: request,
		success: function(message) {
			if (message) {
				zenarioA.showMessage(message);
			}
		}
	});
};


zenarioO.shortenPath = function(path) {
	//path = ('/' + path + '/').replace(
	//	/\/panel\//g, '/').replace(
	//	/\/panels\//g, '/').replace(
	//	/\/refiners\//g, '/').replace(
	//	/\/nav\//g, '/').replace(
	//	/\/collection_buttons\//g, '/').replace(
	//	/\/item_buttons\//g, '/').replace(
	//	/\/inline_buttons\//g, '/').replace(
	//	/\/hidden_nav\//g, '/').replace(
	//	/\/items\//g, '/');
	//
	//return path.substr(1, path.length-2);
	return path;
};


zenarioO.implodeKeys = function(input) {
	var output = '';
	
	foreach (input as var i) {
		output += (output? ',' : '') + i;
	}
	
	return output;
};


zenarioO.getPanelType = function(path) {
	
	if (path = path || zenarioO.path) {
		
		var panelType = zenarioO.followPathOnMap(path, 'panel_type');
	
		if (panelType && zenarioO.panelTypes[panelType]) {
			return panelType;
		}
	}
	
	return 'list';
};


zenarioO.initNewPanelInstance = function(path, refiner) {
	var panelType = zenarioO.getPanelType(path),
		panelInstance = new zenarioO.panelTypes[panelType]();
	
	panelInstance.panelType = panelType;
	panelInstance.cmsSetsPath(path);
	panelInstance.cmsSetsRefiner(refiner);
	panelInstance.init();
	
	return panelInstance;
};


//Go to a series of locations in turn
zenarioO.checkQueue = function(sameLoad) {
	var len;
	if (len = zenarioO.checkQueueLength()) {
		var ids = '',
			panelType, panelInstance,
			queued = window.zenarioOQueue.shift();
		
		//Should any items be selected at this point?
		if (queued.selectedItems) {
			ids = zenarioO.implodeKeys(queued.selectedItems);
			
			if (queued.refinerName) {
				queued.refiner = {name: queued.refinerName, id: ids};
			}
			
			//Normally we'd wait until the zenarioO.go() function to set up the panel instance,
			//but if we have any selected items to record then we'll need to initialise it now
			//in order to record them.
			//So if the panel instance was not loaded previously, create a new one.
			if (!zenarioO.pi
			 || !zenarioO.branches[zenarioO.branches.length-1].panel_instances[zenarioO.path]) {
				
				zenarioO.pi =
				zenarioO.branches[zenarioO.branches.length-1].panel_instances[zenarioO.path] =
					zenarioO.initNewPanelInstance(zenarioO.path, queued.refiner);
			}
			
			//Record the items that were selected on this panel
			zenarioO.pi.cmsSetsSelectedItems(queued.selectedItems);
			
			//zenarioO.pi.selectedItems = queued.selectedItems;
			//zenarioO.saveSelection(true);
		
		} else {
			if (queued.refinerName) {
				queued.refiner = {name: queued.refinerName, id: ids};
			}
		}
		
		//zenarioO.go(path, branch, refiner, queued, lastInQueue, backwards, dontUseCache, itemToSelect, sameLoad, runFunctionAfter, periodic, inCloseUpView, searchTerm) {

		zenarioO.go(queued.path, queued.branch, queued.refiner, true, !--len, undefined, undefined, undefined, sameLoad, undefined, undefined, undefined, queued.searchTerm, queued.filters);
		
		return true;
	} else {
		return false;
	}
};

zenarioO.checkQueueLength = function() {
	if (!window.zenarioOQueue) {
		return 0;
	} else {
		return window.zenarioOQueue.length;
	}
};

//If a Plugin Nest opened this Organizer Window, refresh that nest
zenarioO.reloadOpeningInstanceIfRelevant = function(path) {
	if (!zenarioO.checkQueueLength()) {
	 	
	 	if (path == window.zenarioOOpeningPath
		 && (window.zenarioOOpeningInstance || window.zenarioOReloadOnChanges == 'zenarioAT')) {
			var hash = '';
		
			if (window.zenarioOOpeningInstance) {
				hash = window.zenarioOOpeningInstance + '!_refresh=' + ('' + Math.random()).replace('.', '');
			}
		
			if (window.zenarioOReloadOnChanges == 'zenarioAT') {
				hash += '&__zenario_reload_at__=' + ('' + Math.random()).replace('.', '');
			}
		
			if (windowOpener && windowOpener.zenario) {
				window.opener.document.location.hash = hash;
		
			} else if (windowParent && windowParent.zenario) {
				window.parent.document.location.hash = hash;
			}
		}
	
		if (window.zenarioOReloadOnChanges == 'zenarioO'
		 && windowParent
		 && windowParent.zenarioO
		 && windowParent.zenarioO.init) {
			windowParent.zenarioO.reload();
		}
	}
};



zenarioO.callPanelOnUnload = function() {
	//If there is already a panel being displayed, attempt to call it's onUnload function
	if (zenarioO.path
	 && zenarioO.pi
	 && zenarioO.pi.onUnload) {
		var $header = $('#organizer_header'),
			$panel = $('#organizer_rightColumnContent'),
			$footer = $('#organizer_lowerMiddleColumn');
		zenarioO.pi.onUnload($header, $panel, $footer);
	}
};

zenarioO.pathNotAllowed = function(link) {
	return link && window.zenarioONotFull && window.zenarioOMaxPath !== false && zenarioO.path == window.zenarioOMaxPath && link.path != zenarioO.path;
};


zenarioO.parseNavigationPath = function(pathAndItem, len, includeSelectedItem) {
	
	if (_.isString(pathAndItem)) {
		pathAndItem = pathAndItem.split('//'),
		len = pathAndItem.length;
	}
	
	if (len <= 2) {
		queue = [{path: pathAndItem[0], branch: -1}];
	
	} else {
		
		//Try and parse the path to work out where this should be
		var lastLink, p, link, combinedPath, selectedItems, refinerId, refinerName, linkTo, queue;
		
		for (p = 0; p < len-1; p += 2) {
			
			//Navigate to the start of the branch
			if (p == 0) {
				link = zenarioO.getFromToFromLink(pathAndItem[0]);
				queue = [{path: link.from, branch: -1}];
			
			} else {
				if (pathAndItem[p].substr(0, 1) == '/') {
					combinedPath = pathAndItem[p].substr(1);
				} else {
					combinedPath = lastLink.to + '/' + pathAndItem[p];
				}
				
				link = zenarioO.getFromToFromLink(combinedPath);
				
				if (lastLink.to != link.from) {
					queue.push({path: link.from});
				}
			}
			
			//Select any items 
			selectedItems = {};
			if (pathAndItem[p + 1]) {
				selectedItems = zenarioA.csvToObject(pathAndItem[p + 1]);
			}
			
			//Look for any refiners
			refinerId = false,
			refinerName = false;
			if (link.refiner) {
				refinerName = link.refiner;
				refinerId = pathAndItem[p + 1];
			}
			
			linkTo = link.to;
			
			if (!linkTo) {
				zenarioA.showMessage('The requested path "' + pathAndItem.join('//') + '" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', undefined, 'error', false, true);
				return false;				
			}
			
			//Select an item on the last panel, if asked for
			if (includeSelectedItem && p+2 == len-1) {
				linkTo += '//' + pathAndItem[p+2];
			}
			
			//Do the branch
			queue.push({path: linkTo, branch: true, selectedItems: selectedItems, refinerId: refinerId, refinerName: refinerName});
			
			lastLink = link;
		}
	}
	
	return queue;
};

zenarioO.convertNavPathToTagPathAndRefiners = function(path) {
	
	var queue, i, link,
		out = {path: '', request: {}};
	
	if (queue = zenarioO.parseNavigationPath(path)) {
		
		for (i = 0; i < queue.length; ++i) {
			link = queue[i];
			
			out.path = link.path;
			
			if (link.refinerName) {
				out.request['refiner__' + link.refinerName] =
				out.request.refinerId =
					link.refinerId;
				out.request.refinerName =
					link.refinerName;
			
			} else {
				delete out.request.refinerId;
				delete out.request.refinerName;
			}
		}
		
		return out;
		
	} else {
		return false;
	}
};

zenarioO.convertNavPathToTagPath = function(path) {
	
	var queue;
	if (queue = zenarioO.parseNavigationPath(path)) {
		return queue[queue.length - 1].path;
	} else {
		return path;
	}
};



//Go to a location
zenarioO.goNum = 0;
zenarioO.loadNum = 0;
zenarioO.lastSuccessfulGoNum = 0;
zenarioO.go = function(path, branch, refiner, queued, lastInQueue, backwards, dontUseCache, itemToSelect, sameLoad, runFunctionAfter, periodic, inCloseUpView, searchTerm, filters) {
	
	if (!periodic) {
		zenarioO.lastActivity = Date.now();
	}
	
	if (!zenarioA.isFullOrganizerWindow && !zenarioA.checkIfBoxIsOpen('og')) {
		return;
	}
	
	if (zenarioO.stop) {
		return false;
	}
	
	//If the map has not yet loaded, wait until it has!
	if (!zenarioO.map
	 || !zenarioO.initRun) {
		if (!window.zenarioONotFull) {
			window.zenarioOQueue = [{path: path, branch: -1}];
		}
		return;
	}
	
	//For the min/max/target paths, automatically convert any navigation paths to tag paths
	if (window.zenarioOCheckPaths) {
		delete window.zenarioOCheckPaths;
		
		if (window.zenarioOTargetPath) {
			window.zenarioOTargetPath = zenarioO.convertNavPathToTagPath(window.zenarioOTargetPath);
		}
		if (window.zenarioOMinPath) {
			window.zenarioOMinPath = zenarioO.convertNavPathToTagPath(window.zenarioOMinPath);
		}
		if (window.zenarioOMaxPath && engToBoolean(window.zenarioOMaxPath)) {
			window.zenarioOMaxPath = zenarioO.convertNavPathToTagPath(window.zenarioOMaxPath);
		}
	}
	
	zenarioO.callPanelOnUnload();
	
	
	if (!queued) {
		window.zenarioOQueue = false;
	}
	
	//Look for searches/filters passed in the URL
	var requestedPath,
		varFromHash,
		pos;
	
	if (path !== undefined) {
		pos = path.indexOf('~_');
		if (pos != -1) {
			varFromHash = path.substr(pos + 2);
			path = path.substr(0, pos);
		
			try {
				varFromHash = JSON.parse(decodeURIComponent(varFromHash));
				filters = varFromHash;
			} catch(e) {
			}
		}
		pos = path.indexOf('~-');
		if (pos != -1) {
			varFromHash = path.substr(pos + 2);
			path = path.substr(0, pos);
		
			try {
				varFromHash = decodeURIComponent(varFromHash);
				searchTerm = varFromHash;
			} catch(e) {
			}
		}
	}
	
	
	requestedPath = path;
	
	if (path) {
		
		var pathAndItem = path.split('//'),
			len = pathAndItem.length,
			queue;
		
		//Is there any deeplinking in the path?
		if (len > 2) {
			//Ignore the itemToSelect parameter if it was set
			itemToSelect = undefined;
			inCloseUpView = false;
			
			if (!(queue = zenarioO.parseNavigationPath(pathAndItem, len, true))) {
				return false;
			}
			
			//If we saw a search term and/or filters in the hash, remember them for the last panel.
			//(We don't remember any searches/filters on parent panels in the branches, just the current/deepest one.)
			if (searchTerm !== undefined) {
				queue[queue.length - 1].searchTerm = searchTerm;
			}
			if (filters !== undefined) {
				queue[queue.length - 1].filters = filters;
			}
			
			window.zenarioOQueue = queue;
			zenarioO.checkQueue(true);
			return;
		
		//Is an item requested in the path?
		} else if (len == 2) {
			//Ignore the itemToSelect parameter if it was set
			itemToSelect = undefined;
			inCloseUpView = false;
			
			//Get the tag-path from the item and path
			path = pathAndItem[0];
			
			//Check there are no commas in the id of the selected item,
			//and try and get the item id from the item and path
			if ((pathAndItem[1].indexOf(',') == -1)
			 && (itemToSelect = pathAndItem[1])) {
				//Look for a single slash after the item - this should indicate closeup view
				var len = itemToSelect.length - 1;
				if (itemToSelect.substr(len) == '/') {
					itemToSelect = itemToSelect.substr(0, len);
					inCloseUpView = true;
				}
			}
		
		//Otherwise we'll use the itemToSelect input, and select that item after the jump if possible
		} else {
			if (!itemToSelect) {
				inCloseUpView = false;
			}
		}
		
		path = zenarioO.shortenPath(path);
	}
	
	//If a path has not been set, go to the initial panel
	if (!path) {
		path = window.zenarioONotFull? zenarioO.defaultPathInIframePreload : zenarioO.defaultPath;
		inCloseUpView = false;
	}
	
	if (!zenarioO.followPathOnMap(path)) {
		//add some debug information here
		zenarioA.showMessage('The requested path "' + requestedPath + '" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', undefined, 'error', false, true);
		return false;
	}
	
	
	//Number each request that is made, so we can tell which ones are outdated
	var goNum = ++zenarioO.goNum;
	
	if (!sameLoad) {
		++zenarioO.loadNum;
	}
	
	
	var panelInstance = false,
		advancedValues = false,
		previousRefiner = false;
	
	if (filters === undefined) {
		filters = zenarioO.loadFromBranches(path, branch, 'filters');
	}
	
	if (searchTerm === undefined) {
		searchTerm = zenarioO.loadFromBranches(path, branch, 'searches');
	}

	
	//Check to see if a refiner is set
	if (refiner === undefined) {
		refiner = zenarioO.loadRefiner(path, branch);
	}
	
	//If this panel requires a refiner but none has been set, don't attempt to show it and go to the panel above instead.
	if (!refiner && engToBoolean(zenarioO.followPathOnMap(path, 'refiner_required'))) {
		zenarioA.showMessage('A refiner was required, but none was set.', true, 'error');
		return;
	}
	
	//Attempt to find the refiner used on the panel before this one, if there was one
	var lastRefiners = {},
		branches = zenarioO.branches.length - 1,
		b;
	
	//If we're resetting all branches, there will be no previous refiner and no need to load the panel instance
	if (branch !== -1) {
		//Try to retrieve the panel instance
		panelInstance = zenarioO.loadFromBranches(path, branch, 'panel_instances');
		
		//Get the refiners on each of the branches that we've previously followed
		for (b = 0; b < branches; ++b) {
			var rememberRefiner = zenarioO.branches[b].refiners[zenarioO.branches[b+1].from];
			if (rememberRefiner) {
				lastRefiners['refiner__' + rememberRefiner.name] = rememberRefiner.id;
			}
		}
		
		//If we're branching with this link, get the refiner on the current panel
		//if (branch) {
			var rememberRefiner = zenarioO.branches[branches].refiners[zenarioO.path];
			if (rememberRefiner) {
				lastRefiners['refiner__' + rememberRefiner.name] = rememberRefiner.id;
			}
		//}
		
		//Add the current refiner, if there is one
		if (refiner) {
			lastRefiners['refiner__' + refiner.name] = refiner.id;
		}
	}
	
	//If the panel instance was not loaded previously, create a new one.
	if (!panelInstance) {
		panelInstance = zenarioO.initNewPanelInstance(path, refiner);
	} else {
		panelInstance.cmsSetsPath(path);
		panelInstance.cmsSetsRefiner(refiner);
	}
	
	
	var defaultSortColumn = zenarioO.followPathOnMap(path, 'default_sort_column');
	if (!defaultSortColumn) {
		defaultSortColumn = 'name';
	}
	
	//Load the TUIX of this panel from the "map". The map is a static version of all of the TUIX files
	//that make up Organizer.
	//To keep the inital download size small when first opening Organizer, maybe properties are trimmed
	//from the map to save space.  The zenarioParseTUIX() function in zenario/includes/admin.inc.php
	//controls the logic for which tags are not trimmed.
	var panel = zenarioO.followPathOnMap(path);
	
	//Let the panel instance know about the TUIX properties from the map
	panelInstance.cmsSetsPanelTUIX(panel);
	
	//Let the panel instance know which item was selected
	panelInstance.cmsSetsRequestedItem(itemToSelect);
	
	//Let the panel instance know what the search term was
	panelInstance.cmsSetsSearchTerm(searchTerm);
 	
 	//Check to see if there were any filters previously set on this panel
	if (filters && !_.isEmpty(filters)) {
		//If so, use them
		filters = $.extend(true, {}, filters);
	
	} else {
		//Otherwise start with empty filters
		filters = {};
	}
	
	//Look through the filters on this page, checking to see if any have been set.
	//If any are not set them strip them away as we don't need any junk data.
	foreach (filters as var c => var filter) {
		if (zenarioO.filterSetOnColumn(c, filters)) {
			
			
			//A couple of little hacks to save space here:
				//There's no difference between {not: false} and {not: undefined} so we can delete "not" if it is false
				//Also "not" and "show" is used a as a boolean, so we can convert {s: true} to {s: 1} and {s: false} to {s: 0}
			if (filter.not !== undefined) {
				if (!filter.not) {
					delete filter.not;
				} else {
					filter.not = 1;
				}
			}
			if (filter.s !== undefined) {
				filter.s = engToBoolean(filter.s);
			}
			
		//However leave an empty object so we can tell the different between specifically turning something off,
		//and something that was never turned on
		} else if (filters[c]) {
			filters[c] = {};
		}
	}
	
	
	var db_items = panel.db_items,
		reorder = panel.reorder,
		server_side = panelInstance.returnDoSortingAndSearchingOnServer(),
		thisPageSize = 1*panelInstance.returnPageSize(),
		url = panelInstance.returnAJAXURL(),
		devToolsURL = panelInstance.returnDevToolsAJAXURL(),
		requests = {},
		data = false,
		store,
		go2 = function(data) {
			zenarioO.go2(path, url, devToolsURL, requests, branch, goNum, defaultSortColumn, thisPageSize, inCloseUpView, itemToSelect, panelInstance, searchTerm, filters, refiner, lastRefiners, server_side, backwards, runFunctionAfter, data);
		};
	
	if (url) {
		if (window.zenarioONotFull) {
			if (window.zenarioOSelectMode) {
				url += '&_select_mode=1';
			} else if (window.zenarioOQuickMode) {
				url += '&_quick_mode=1';
			}
		}
	
		if (queued && !lastInQueue) {
			requests._queued = 1;
		}
	
		if (refiner !== undefined) {
			requests.refinerId = refiner.id;
			requests.refinerName = refiner.name;
		
			if (refiner.languageId) {
				requests.languageId = refiner.languageId;
			}
		}
	
		if (window.zenarioOCombineItem) {
			requests._combineItem = window.zenarioOCombineItem;
		}
	
		foreach (lastRefiners as var f) {
			requests[f] = lastRefiners[f];
		}
	
		//Pagination logic
		if (server_side || zenarioO.CSVExport) {
			if (thisPageSize) {
				requests._limit = thisPageSize;
		
				if (zenarioO.refreshToPage) {
					requests._start = (zenarioO.refreshToPage-1) * thisPageSize;
				} else {
					requests._start = 0;
				}
		
				if (itemToSelect) {
					requests._item = itemToSelect;
		
				//Try to get the previously selected item from the previous panel, if there was one
				} else if (backwards) {
					var requestItem;
			
					if (typeof backwards == 'object' && backwards.selectedItemFromLastPanel) {
						requests._item = backwards.selectedItemFromLastPanel;
					} else if (requestItem = zenarioO.getSelectedItemFromLastPanel(path)) {
						requests._item = requestItem;
					}
				}
			}
		
			//Work out which column to sort on
			//Sort by the reorder column if we're reordering
			if (reorder && reorder.column) {
				requests._sort_col = reorder.column;
		
			//Look up the user's choice of sort column
			} else if (zenarioO.prefs[path] && zenarioO.prefs[path].sortBy) {
				requests._sort_col = zenarioO.prefs[path].sortBy;
				requests._sort_desc = zenarioO.prefs[path].sortDesc? 1 : 0;
		
			//Otherwise sort by the default sort column
			} else {
				requests._sort_col = defaultSortColumn;
				requests._sort_desc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
			}
		}
		
		//Send the values of any filters
		if (filters && !_.isEmpty(filters)) {
			requests._filters = JSON.stringify(filters);
		}
	
		if (server_side || zenarioO.CSVExport) {
			if (searchTerm !== undefined) {
				requests._search = searchTerm;
			}
		}
	}
	
	//Disable the current panel/page from refreshing
	zenarioO.stopRefreshing();
	
	if (path != zenarioO.defaultPathInIframePreload) {
		get('organizer_preloader_circle').style.display = 'block';
	}
	
	//Refresh the opener, if needed
	if (window.zenarioOFirstLoad !== undefined) {
		if (window.zenarioOFirstLoad) {
			window.zenarioOFirstLoad = false;
		} else {
			zenarioO.reloadOpeningInstanceIfRelevant(path);
		}
	}
	
	
	//Allow the panel type to disable this AJAX request by returning false for the url
	if (!url) {
		go2(panel);
	} else {
		zenario.ajax(url + zenario.urlRequest(requests), false, true, true, true).after(go2);
	}
	
	//Attempt to get this panel from the local storage, unless there's a post request
	//} else if (postUsed) {
	//	//Don't use the local storage for post requests
	//	zenarioA.keepTrying(function(attempt) {
	//		$.ajax({
	//			url: url + zenario.urlRequest(requests),
	//			data: post,
	//			type: 'POST',
	//			dataType: 'text'
	//		
	//		}).fail(function(resp, statusType, statusText) {
	//			if (zenarioA.stopTrying(attempt)) {
	//				zenarioA.AJAXErrorHandler(resp, statusType, statusText);
	//			}
	//		
	//		}).done(function(data) {
	//			if (zenarioA.stopTrying(attempt)) {
	//				if (data = zenarioA.readData(data)) {
	//					go2(data);
	//				}
	//			}
	//		});
	//	});
	//
	//} else if (db_items && !dontUseCache && (store = zenario.checkSessionStorage(url, requests, true, zenarioO.loadNum))) {
	//	//Use the value from the local storage if possible...
	//	go2(store);
	//
	//} else {
	//	//...otherwise do an AJAX request then save the results into the local storage
	//	var startTrying = function() {
	//		zenarioA.keepTrying(function(attempt) {
	//			$.ajax({
	//				url: url,
	//				data: requests,
	//				dataType: 'text'
	//		
	//			}).fail(function(resp, statusType, statusText) {
	//				if (zenarioA.stopTrying(attempt)) {
	//					resp.zenario_retry = startTrying;
	//					zenarioA.AJAXErrorHandler(resp, statusType, statusText);
	//				}
	//		
	//			}).done(function(data) {
	//				if (zenarioA.stopTrying(attempt)) {
	//					if (data = zenarioA.readData(data, url, requests, startTrying)) {
	//						go2(data);
	//					}
	//				}
	//			});
	//		});
	//	};
	//	startTrying();
	//}
};



//Tidy these long params up into an object..?

//Part 2 of the go function, run when we've got the data back from the server
	//(Or run straight away if the server was never polled)
zenarioO.go2 = function(path, url, devToolsURL, requests, branch, goNum, defaultSortColumn, thisPageSize, inCloseUpView, itemToSelect, panelInstance, searchTerm, filters, refiner, lastRefiners, server_side, backwards, runFunctionAfter, data) {
	
	//For debugging
	if (data
	 && data.comment !== undefined
	 && window.console) {
		console.log(data.comment);
	}
	
	if (!zenarioA.isFullOrganizerWindow && !zenarioA.checkIfBoxIsOpen('og')) {
		return;
	}
	
	//Check that this isn't an out-of-date request that has come in syncronously via AJAX
	if (goNum != zenarioO.goNum) {
		return;
	}
	
	zenarioO.stopRefreshing();
	
	////If this is a different panel, scroll back up to the top
	//if (zenarioO.path != path || branch === true || branch === -1) {
	//	zenarioO.scrollToPageTop = true;
	//}
	
	var lastPath = zenarioO.path,
		lastTitle,
		noReturnEnabled;
	
	if (zenarioO.tuix) {
		lastTitle = zenarioO.tuix.title;
		noReturnEnabled = engToBoolean(zenarioO.tuix.no_return);
	}
	
	//This variable is used by dev tools to load dev information on the last panel
	zenarioO.url = url + zenario.urlRequest(requests);
	
	//If a panel type has overridded the returnAJAXURL() function,
	//record what the output would have been so that we can still use the dev tools
	if (devToolsURL != url) {
		zenarioO.devToolsURL = devToolsURL + zenario.urlRequest(requests);
	} else {
		zenarioO.devToolsURL = undefined;
	}
	
	//This variable is used later to launch a second AJAX request, if we need to repeat
	//the same query with the same search/filters as before
	zenarioO.lastRequests = requests;
	
	zenarioO.path = path;
	zenarioO.defaultSortColumn = defaultSortColumn;
	zenarioO.thisPageSize = thisPageSize;
	zenarioO.server_side = server_side;
	zenarioO.inspectionView = inCloseUpView;
	zenarioO.inspectionViewItem = itemToSelect;
	
	if (!zenarioO.prefs[path]) {
		zenarioO.prefs[path] = {};
	}
	
	
	//Add functionality for multiple "branches"
	if (branch === -1) {
		//branch = -1 turns off all previously collected branches
		zenarioO.resetBranches();
	} else if (branch) {
		//branch = true turns this link into a new branch
		zenarioO.branch(path, lastPath, lastTitle, noReturnEnabled);
	}
		//Otherwise branch = undefined continues in the current branch
	
	
	//Save the instance to this part of the navigation so it can be loaded again if the admin
	//ever comes back to the panel.
	zenarioO.pi =
	zenarioO.branches[zenarioO.branches.length-1].panel_instances[zenarioO.path] =
		panelInstance;
	
	//Save filters
	if (data._filters !== undefined) {
		filters = data._filters;
		delete data._filters;
	}
	zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path] = filters;
	//zenarioO.branches[zenarioO.branches.length-1].searches[zenarioO.path] = searchTerm;
	zenarioO.saveSearch(searchTerm);
	//zenarioO.searchTerm = searchTerm
	
	//Focus on this part of the map
	delete zenarioO.tuix;
	zenarioO.tuix = data;
		//Backwards compatability for any old code
		zenarioO.focus = zenarioO.tuix;	
	
	zenarioO.pi.cmsSetsPanelTUIX(zenarioO.tuix);
	
	
	//Setup filters
	zenarioO.filtersSet = false;
	zenarioO.filtersSetInViewOptions = false;
	foreach (filters as var c => var filter) {
		if (zenarioO.filterSetOnColumn(c, filters)) {
			zenarioO.filtersSet = true;
			
			if (zenarioO.canFilterColumn(c)) {
				zenarioO.filtersSetInViewOptions = true;
				break;
			}
		}
	}
	
	zenarioO.markIfViewIsFiltered();
	
	
	
	zenarioO.saveRefiner(refiner);
	zenarioO.lastRefiners = lastRefiners;
	
	
	
	//Check if we have a column description, and set a default one if not
	if (!zenarioO.tuix.columns) {
		zenarioO.tuix.columns = {'name': {'title': 'Name'}};
	}
	
	
	//Handle preferences
	//Sort by the reorder column if we're reordering
	var defaultSortColumn, reorder = zenarioO.followPathOnMap(path, 'reorder');
	if (reorder && reorder.column) {
		zenarioO.sortBy = reorder.column;
		zenarioO.sortDesc = false;
	
	//Look up the user's choice of search column
	} else if (zenarioO.prefs[path].sortBy && zenarioO.tuix.columns[zenarioO.prefs[path].sortBy]) {
		zenarioO.sortBy = zenarioO.prefs[path].sortBy;
		zenarioO.sortDesc = zenarioO.prefs[path].sortDesc;
	
	//Sort by the default sort column if this is defined
	} else if (defaultSortColumn = zenarioO.followPathOnMap(path, 'default_sort_column')) {
		zenarioO.sortBy = defaultSortColumn;
		zenarioO.sortDesc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
	
	//Otherwise sort by name by default
	} else {
		zenarioO.sortBy = zenarioO.defaultSortColumn;
		zenarioO.sortDesc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
	}
	zenarioO.pi.cmsSetsSortColumn(zenarioO.sortBy, zenarioO.sortDesc);
	
	zenarioO.sortedColumns = zenarioO.getSortedIdsOfTUIXElements('columns');
	zenarioO.sortedItemButtons = zenarioO.getSortedIdsOfTUIXElements('item_buttons');
	zenarioO.sortedInlineButtons = zenarioO.getSortedIdsOfTUIXElements('inline_buttons');
	zenarioO.sortedCollectionButtons = zenarioO.getSortedIdsOfTUIXElements('collection_buttons');
	zenarioO.sortedQuickFilterButtons = zenarioO.getSortedIdsOfTUIXElements('quick_filter_buttons');
	
	//Work out which columns should be shown
	zenarioO.shownColumns = zenarioO.getShownColumns(path, defaultSortColumn, zenarioO.tuix.columns);
	
	//Check if the Admin has a customised sort order on this Panel
	if (zenarioO.prefs[path].sortedColumns) {
		//Get an array-flip of their sorted columns, so we can look up each column's order
		var existingSortedColumns = {};
		foreach (zenarioO.prefs[path].sortedColumns as var colNo) {
			var c = zenarioO.prefs[path].sortedColumns[colNo];
			existingSortedColumns[c] = colNo;
		}
		
		//Loop through the columns in the current view...
		var lastColName = false;
		foreach (zenarioO.sortedColumns as var colNo) {
			var c = zenarioO.sortedColumns[colNo];
			
			if (zenarioO.isShowableColumn(c, false, true)) {
				//...checking to see if there are any newly added columns
				if (existingSortedColumns[c] === undefined) {
					//If so, attempt to find a new place for this column by looking at where the column before it was
					var colPos = existingSortedColumns[lastColName];
					
					if (colPos === undefined) {
						colPos = 0;
					} else {
						colPos = 1*colPos + 1;
					}
					
					//Add the column in
					zenarioO.prefs[path].sortedColumns.splice(colPos, 0, c);
					
					//Update the array of placements
					foreach (existingSortedColumns as var d) {
						if (existingSortedColumns[d] >= colPos) {
							++existingSortedColumns[d]
						}
					}
					
					existingSortedColumns[c] = colPos;
				}
			}
			
			lastColName = c;
		}
		
		zenarioO.sortedColumns = zenarioO.prefs[path].sortedColumns;
	}
	
	
	
	if (zenarioO.tuix.items && zenarioO.tuix.__item_count__ == 1 && engToBoolean(zenarioO.tuix.allow_bypass)) {
		zenarioO.branches[zenarioO.branches.length-1].bypasses[zenarioO.path] = true;
	} else {
		delete zenarioO.branches[zenarioO.branches.length-1].bypasses[zenarioO.path];
	}
	
	
	//Check the queue to see if there are any more navigation commands queued after this destination. If so, run them, and stop processing this one
	if (zenarioO.checkQueue(true)) {
		return;
	}
	
	//If there is only one item and "allow_bypass" is enabled, skip right past this panel
	if (zenarioO.tuix.items && zenarioO.tuix.__item_count__ == 1 && engToBoolean(zenarioO.tuix.allow_bypass)) {
		if (backwards) {
			if (zenarioO.getBackButtonTitle() !== false) {
				zenarioO.back();
				return;
			}
		} else {
			var link;
			if (zenarioO.tuix.items) {
				foreach (zenarioO.tuix.items as var id) {
					var selectedItems = {};
					selectedItems[id] = true;
					zenarioO.pi.cmsSetsSelectedItems(selectedItems);
					//zenarioO.saveSelection();
					
					if (zenarioO.itemClickThroughAction(id)) {
						return;
					} else {
						break;
					}
				}
			}
		}
	
	//If there are no items in this Panel and the return_if_empty flag is set, attempt to go back
	} else if ((!zenarioO.tuix.items || !zenarioO.tuix.__item_count__) && engToBoolean(zenarioO.tuix.return_if_empty)) {
		if (zenarioO.getBackButtonTitle() !== false) {
			zenarioO.back();
			return;
		}
	}
	
	
	//Look to see if there are any item_link type columns
	var menuIds = {},
		contentTags = {},
		otherItemLinks = {},
		cTypes = {},
		value, values,
		cTypeAndId,
		i, c, col, path,
		lang, parent, url;
	
	foreach (zenarioO.tuix.columns as c => col) {
		if (zenarioO.isShowableColumn(c)) {
			if (path = col.item_link) {
				switch (path) {
					case 'content_item':
					case 'content_item_or_url':
				
						foreach (zenarioO.tuix.items as i) {
							if (value = zenarioO.tuix.items[i][c]) {
								cTypeAndId = value.split('_');
								if (cTypeAndId[0]
								 && cTypeAndId[0] != 'null'
								 && cTypeAndId[0] === cTypeAndId[0].replace(/\W/, '')
								 && cTypeAndId[1]
								 && cTypeAndId[1] == 1*cTypeAndId[1]) {
									lang = zenarioO.itemLanguage(i),
									parent = zenarioO.itemParent(i);
							
									if (!contentTags[lang]) {
										contentTags[lang] = {};
									}
							
									if (!contentTags[lang][parent]) {
										contentTags[lang][parent] = '';
									} else {
										contentTags[lang][parent] += ',';
									}
							
									contentTags[lang][parent] += cTypeAndId[0] + '_' + cTypeAndId[1];
								}
							}
						}
						
						break;
					
					case 'menu_item':
						foreach (zenarioO.tuix.items as i) {
							if ((value = zenarioO.tuix.items[i][c])
							 && (value == 1*value)) {
								lang = zenarioO.itemLanguage(i);
						
								if (!menuIds[lang]) {
									menuIds[lang] = '';
								} else {
									menuIds[lang] += ',';
								}
						
								menuIds[lang] += value;
							}
						}
						
						break;
					
					default:
						foreach (zenarioO.tuix.items as i) {
							if (value = zenarioO.tuix.items[i][c]) {
								if (!otherItemLinks[path]) {
									otherItemLinks[path] = '';
								} else {
									otherItemLinks[path] += ',';
								}
				
								otherItemLinks[path] += value;
							}
						}
				}
			}
		}
	}
	
	
	//Launch AJAX requests for each of the item links
	//As there may be more than one we'll launch them syncronously to save time
	//When all the requests have been completed we'll then proceed to the go3 function.
	zenarioO.contentItems = {};
	zenarioO.menuItems = {};
	zenarioO.otherItemLinks = {};
	zenarioO.itemLinkRequestsLeft = 0;
	zenarioO.shallowLinks = {'content_item': 'zenario__content/panels/content', 'content_item_or_url': 'zenario__content/panels/content', 'menu_item': 'zenario__menu/panels/menu_nodes'};
	
	foreach (contentTags as lang) {
		foreach (contentTags as parent) {
			if (contentTags[lang][parent]) {
			
				if (!zenarioO.contentItems[lang]) {
					zenarioO.contentItems[lang] = {}
				}
				if (!zenarioO.contentItems[lang][parent]) {
					zenarioO.contentItems[lang][parent] = {}
				}
			
				url =
					URLBasePath +
					'zenario/admin/organizer.ajax.php?path=' + zenarioO.shallowLinks['content_item'] +
					'&_get_item_links=' + contentTags[lang][parent] +
					'&languageId=' + encodeURIComponent(lang);
				
				//if (!(zenarioO.contentItems[lang][parent] = zenario.checkSessionStorage(url, {}, true, zenarioO.loadNum))) {
					++zenarioO.itemLinkRequestsLeft;
					zenarioO.getDataHack(url, lang, function(lang, data) {
						if (goNum == zenarioO.goNum) {
							zenarioO.contentItems[lang][parent] = data;
							if (!--zenarioO.itemLinkRequestsLeft) {
								zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
							}
						}
					});
				//}
			}
		}
	}
	
	foreach (menuIds as lang => values) {
		if (values) {
			
			if (!zenarioO.menuItems[lang]) {
				zenarioO.menuItems[lang] = {}
			}
			
			url =
				URLBasePath +
				'zenario/admin/organizer.ajax.php?path=' + zenarioO.shallowLinks['menu_item'] +
				'&_get_item_links=' + encodeURIComponent(values) +
				'&languageId=' + encodeURIComponent(lang) +
				'&refinerName=language' +
				'&refinerId=' + encodeURIComponent(lang) +
				'&refiner__language=' + encodeURIComponent(lang);
			
			//if (!(zenarioO.menuItems[lang] = zenario.checkSessionStorage(url, {}, true, zenarioO.loadNum))) {
				++zenarioO.itemLinkRequestsLeft;
				zenarioO.getDataHack(url, lang, function(lang, data) {
					if (goNum == zenarioO.goNum) {
						zenarioO.menuItems[lang] = data;
						if (!--zenarioO.itemLinkRequestsLeft) {
							zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
						}
					}
				});
			//}
		}
	}
	
	foreach (otherItemLinks as path => values) {
		if (values) {
			
			if (!zenarioO.otherItemLinks[path]) {
				zenarioO.otherItemLinks[path] = {}
			}
			
			url =
				URLBasePath +
				'zenario/admin/organizer.ajax.php?path=' + encodeURIComponent(path) +
				'&_get_item_links=' + encodeURIComponent(values);
			
			//if (!(zenarioO.otherItemLinks[path] = zenario.checkSessionStorage(url, {}, true, zenarioO.loadNum))) {
				++zenarioO.itemLinkRequestsLeft;
				zenarioO.getDataHack(url, path, function(path, data) {
					if (goNum == zenarioO.goNum) {
						zenarioO.otherItemLinks[path] = data;
						if (!--zenarioO.itemLinkRequestsLeft) {
							zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
						}
					}
				});
			//}
		}
	}
	
	//If there were no item links to look up, we don't need to wait for any AJAX requests before continuing
	if (!zenarioO.itemLinkRequestsLeft) {
		zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
	
	//If there were, set a timeout on them so that the panel will eventually display even if one or two times out
	} else {
		if (zenarioO.go3Timeout) {
			clearTimeout(zenarioO.go3Timeout);
		}
		zenarioO.go3Timeout =
			setTimeout(
				function() {
					zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
				}, zenarioO.getItemLinkTimeoutTime);
	}
};

//A wrapper function to ensure that the url, lang variables keep their values, rather than get overwritten
zenarioO.getDataHack = function(url, lang, success) {
	
	zenario.ajax(url, url.length > 2000, true, true, true).after(function(data) {
		success(lang, data);
	});
	
	//setTimeout(
	//	function() {
	//		//Hack to try and prevent a possible bug where the GET request gets too large:
	//			//Convert it to a post request if it gets too big
	//		var post = url.length > 2000,
	//			data = zenario.nonAsyncAJAX(url, post);
	//		success(url, lang, data);
	//	}, 1);
};


zenarioO.go3 = function(goNum, searchTerm, backwards, runFunctionAfter) {
	
	if (!zenarioA.isFullOrganizerWindow && !zenarioA.checkIfBoxIsOpen('og')) {
		return;
	}
	
	//Check that this isn't an out-of-date request that has come in syncronously via AJAX
	if (goNum != zenarioO.goNum) {
		return;
	} else {
		zenarioO.lastSuccessfulGoNum = ++zenarioO.goNum;
	}
	
	if (zenarioO.go3Timeout) {
		clearTimeout(zenarioO.go3Timeout);
	}
	
	get('organizer_preloader_circle').style.display = 'none';
	
	zenarioO.setNavigation();
	
	//Display a pop-up message, if this is not a refresh and one has been requested
	if (!backwards && zenarioO.tuix.popout_message) {
		if (zenarioO.refreshToPage === undefined || zenarioO.tuix.popout_message != zenarioO.lastPopoutMessage) {
			zenarioA.showMessage(zenarioO.tuix.popout_message, true, false);
		}
		
		zenarioO.lastPopoutMessage = zenarioO.tuix.popout_message;
	}
	
	
	zenarioO.searchAndSortItems(searchTerm);
	
	//(Periodic refresh is now disabled)
	//if (!engToBoolean(zenarioO.tuix.disable_periodic_refresh)) {
	//	zenarioO.periodicRefresh();
	//}
	
	if (!zenarioO.firstLoaded) {
		zenarioO.firstLoaded = true;
		zenarioA.hideAJAXLoader();
	}
	
	zenarioO.setWrapperClass('loaded', true);
	zenarioO.setWrapperClass('filters_set', zenarioO.filtersSet);
	zenarioO.setWrapperClass('filters_set_in_view_options', zenarioO.filtersSetInViewOptions);
	
	if (runFunctionAfter) {
		runFunctionAfter();
	}
};

zenarioO.setWrapperClass = function(className, active) {
	$('#organizer__box_inner')
		.removeClass('organizer_' + (active? 'not_' : '') + className)
		.addClass('organizer_' + (active? '' : 'not_') + className);
};


zenarioO.itemLanguage = function(i) {
	
	if (i && zenarioO.tuix && zenarioO.tuix.items && zenarioO.tuix.items[i]
	 && (!zenarioO.tuix.items[i].css_class || ('' + zenarioO.tuix.items[i].css_class).indexOf('ghost') == -1)
	 && zenarioO.tuix.items[i].language_id) {
		return zenarioO.tuix.items[i].language_id;
	
	} else if (zenarioO.tuix && zenarioO.tuix.key) {
		return ifNull(zenarioO.tuix.key.languageId, zenarioO.tuix.key.language);
	
	} else {
		return '';
	}
};



zenarioO.itemParent = function(i) {
	
	//if (i && zenarioO.tuix && zenarioO.tuix.items && zenarioO.tuix.items[i]
	// && zenarioO.tuix.db_items && zenarioO.tuix.db_items.hierarchy_column) {
	//
	//	return zenarioO.tuix.items[i][zenarioO.tuix.db_items.hierarchy_column] || '';
	//
	//} else {
		return '';
	//}
};


//Create a list of which columns are to be shown.
zenarioO.getShownColumns = function(path, defaultSortColumn, columns) {	
	var firstColumn = false;
	var columnsShown = false;
	var shownColumns = {};
	
	if (zenarioO.prefs && zenarioO.prefs[path] && zenarioO.prefs[path].shownColumns) {
		shownColumns = zenarioO.prefs[path].shownColumns;
	}
	
	if (columns) {
		foreach (columns as var c) {
			if (!firstColumn) {
				firstColumn = c;
			}
			
			if (engToBoolean(columns[c].always_show)) {
				columnsShown = shownColumns[c] = true;
			
			} else if (shownColumns[c] !== undefined) {
				if (shownColumns[c]) {
					columnsShown = true;
				}
			
			} else {
				if (engToBoolean(columns[c].show_by_default)) {
					columnsShown = shownColumns[c] = true;
				}
			}
		}
	}
	//Show the first column by default, if all others are turned off
	if (!columnsShown) {
		shownColumns[ifNull(firstColumn, defaultSortColumn)] = true;
	}
	
	return shownColumns;
};


//zenarioO.scrollToPageTop = false;
zenarioO.nextPage = function() {
	
	if (zenarioO.lockPageClicks) {
		return false;
	}
	
	if (zenarioO.page < zenarioO.pageCount) {
		//zenarioO.scrollToPageTop = true;
		zenarioO.goToPage(zenarioO.page + 1);
	}
};

//zenarioO.scrollToPageBottom = false;
zenarioO.prevPage = function() {
	
	if (zenarioO.lockPageClicks) {
		return false;
	}
	
	if (zenarioO.page > 1) {
		//zenarioO.scrollToPageBottom = true;
		//zenarioO.scrollToPageTop = true;
		zenarioO.goToPage(zenarioO.page - 1);
	}
};

zenarioO.goToPage = function(page) {
	if (zenarioO.stop) {
		return false;
	}
	
	if (zenarioO.page != page) {
		zenarioO.deselectAllItems();
	}
	
	zenarioO.lockPageClicks = true;
	zenarioO.inspectionView = false;
	
	if (zenarioO.server_side) {
		zenarioO.refreshAndShowPage(page);
	} else {
		zenarioO.showPage(page);
	}
};

zenarioO.goToLastPage = function() {
	if (zenarioO.stop) {
		return false;
	}
	
	zenarioO.goToPage(zenarioO.pageCount);
};

zenarioO.refreshAndShowPage = function(page) {
	zenarioO.page = ifNull(page, 1);
	zenarioO.load()
};

zenarioO.showPage = function(page) {
	zenarioO.page = page;
	zenarioO.setPanel();
};

zenarioO.searchAndSortItems = function(searchTerm) {
	var id,
		matches,
		numeric;
	
	//Show/hide the search box, and set the search term
		//(Don't do this if this was a page refresh, not a page jump)
	if (zenarioO.refreshToPage === undefined) {
		zenarioO.setSearch(searchTerm);
	}
	
	
	
	
	
	if (zenarioO.server_side) {
		//If server side sorting and searching is being used, we can use the sort order from the server as
		//our searched and sorted list.
		//(Note that it's not possible to sort on the server but search on the client.)
		zenarioO.searchedItems = zenarioO.tuix.__item_sort_order__;
		zenarioO.searchMatches = zenarioO.tuix.__item_count__;
	
	} else {
		
		
		if (zenarioO.searchTerm === undefined || get('organizer_search').style.visibility == 'hidden') {
			zenarioO.searchedItems = zenarioO.pi.sortAndSearchItems();
		} else {
			zenarioO.searchedItems = zenarioO.pi.sortAndSearchItems(searchTerm);
		}
		
		zenarioO.searchMatches = zenarioO.searchedItems.length;
	}
	
	zenarioO.itemsOrder = {};
	foreach (zenarioO.searchedItems as var itemNo => id) {
		zenarioO.itemsOrder[id] = 1*itemNo;
	}
	
	var ord, page = 1;
	
	if (zenarioO.thisPageSize) {
		//Work out the current page number for server-side searching and sorting
		if (zenarioO.server_side && zenarioO.server_side) {
			page = ifNull(zenarioO.tuix.__page__, zenarioO.refreshToPage, 1);
	
		//Work out the current page number for client-side sorting
		} else {
			if (zenarioO.refreshToPage !== undefined) {
				page = zenarioO.refreshToPage;
			} else {
				//If we've not got a specific target, check if any items were selected here and use the first we find as a target
				if (!zenarioO.inspectionViewItem) {
					var selectedItems = zenarioO.pi.returnSelectedItems();
					foreach (selectedItems as id) {
						zenarioO.inspectionViewItem = id;
						break;
					}
				}
			
				//If the item we want is in the results, make sure we show that page
				if (ord = zenarioO.itemsOrder[zenarioO.inspectionViewItem]) {
					page = Math.floor(ord / zenarioO.thisPageSize) + 1;
				}
			}
		}
	}
	
	
	delete zenarioO.refreshToPage;
	zenarioO.showPage(page);
};

zenarioO.stop = false;
zenarioO.stopWarningMessage = false;
zenarioO.disableInteraction = function(warningMessage) {
	zenarioO.stop = true;
	if (warningMessage) {
		zenarioO.stopWarningMessage = warningMessage;
	}
	$('#organizer_search_term').prop('disabled', true);
	zenarioO.setWrapperClass('interaction_disabled', true);
};

zenarioO.enableInteraction = function() {
	zenarioO.stop = false;
	zenarioO.stopWarningMessage = false;
	$('#organizer_search_term').prop('disabled', false);
	zenarioO.setWrapperClass('interaction_disabled', false);
};


zenarioO.setPanel = function() {
	
	var i,
		$header = $('#organizer_header'),
		$panel = $('#organizer_rightColumnContent'),
		$footer = $('#organizer_lowerMiddleColumn'),
		itemsGone = false;
		n = 0;
	
	
	//zenarioO.loadSelection();
	
	if (window.zenarioOSelectMode) {
		zenarioO.multipleSelectEnabled = window.zenarioOMultipleSelect;
	} else {
		//Check to see if any item toolbar buttons allow for multiple selections, and enable multiple select if so
		zenarioO.multipleSelectEnabled = zenarioO.pi.returnMultipleSelectEnabled();
	}
	
	var selectedItems = _.clone(zenarioO.pi.returnSelectedItems());
	
	foreach (selectedItems as var i) {
		//Remove any items that have disappeared since this panel was last shown
		if (!zenarioO.tuix.items[i]) {
			delete selectedItems[i];
			itemsGone = true;
		
		} else {
			++n;
		}
	}
	
	//T10250, When deleting an item in Organizer, select the next one
	//If the admin just pressed a button, and that caused the item(s) that were selected to disappear,
	//then try to select the next item.
	if (itemsGone && n == 0) {
		if (zenarioO.selectNextItem
		 && zenarioO.tuix.items[zenarioO.selectNextItem]) {
			selectedItems[zenarioO.selectNextItem] = true;
			++n;
		}
	
	//If multiple-select is not enabled, ensure we've not got too many items selected
	} else if (!zenarioO.multipleSelectEnabled && n > 1) {
		selectedItems = {};
		n = 0;
	}
	delete zenarioO.selectNextItem;
	
	//If we don't have any record of any selected items, but we were asked to select one in the URL,
	//then select that one
	if (n == 0
	 && zenarioO.inspectionViewItem
	 && zenarioO.tuix.items[zenarioO.inspectionViewItem]) {
		n = 1;
		selectedItems = {};
		selectedItems[zenarioO.inspectionViewItem] = true;
	
	//Make sure we don't try to show inspection view if more than one item is selected
	} else if (n > 1
	 || !zenarioO.inspectionViewItem
	 || !zenarioO.tuix.items[zenarioO.inspectionViewItem]) {
		zenarioO.inspectionView = false;
	}
	
	zenarioO.pi.cmsSetsSelectedItems(selectedItems);
	
	//Set the colour of the debug button
	var debugButton;
	if (debugButton = get('organizer_debug_button')) {
		
		//Grey: normal, where there is a navigation path, no refiner.
		if (!zenarioO.refiner) {
			debugButton.className = 'zenario_debug zenario_debug_with_no_refiner';
		
		//Yellow: e.g. the Trash, where refiner name is set but refiner ID is not set.
		} else if (!zenarioO.refiner.id) {
			debugButton.className = 'zenario_debug zenario_debug_with_refiner_and_no_id';
		
		//Orange: e.g. where admin has selected something and views "its" panel, where refiner name is set and there is a refiner ID.
		} else {
			debugButton.className = 'zenario_debug zenario_debug_with_refiner_and_id';
		}
	}
	
	
	zenarioO.enableInteraction();
	
	
	zenarioO.setPanelTitle();
	zenarioO.setBackButton();
	zenarioO.getPageCount();
	
	zenarioO.pi.showPanel($header, $panel, $footer);
	
	if (zenarioO.tuix.toast) {
		zenarioA.toast(zenarioO.tuix.toast);
	}
	
	
	
	//Add $('#organizer_search') ..?
	
	zenarioO.setButtons();
	zenarioO.setTrash();
	
	
	//Check that Organizer and the panel are the right size
	zenarioO.size(true);
	
	
	//zenarioO.scrollToPageTop = false;
	//zenarioO.scrollToPageBottom = false;
	zenarioO.refreshIsPeriodic = false;
	
	//Is this line needed..?
	zenarioO.lockPageClicks = false;
	
	//Set the hash in the browser bar
	zenarioO.setHash();
	
	// Open help tour
	if (zenarioA.show_help_tour_next_time && !zenarioA.seen_help_tour) {
		// Get current nav
		//var topLevel = zenarioO.currentTopLevelPath.split('/')[0];
		// Always use this nav
		var topLevel = 'dummy_item';
		zenarioA.showTutorial(topLevel, true);
	}
	zenarioA.seen_help_tour = true;
	
	//Little hack to fix a few sizing bugs:
	//Run another size check after all of the scripts have finished running.
	setTimeout(function() {
		zenarioO.size(true);
	}, 0);
};



//Go backwards, if possible
zenarioO.back = function(times) {
	if (zenarioO.stop) {
		if (zenarioO.stopWarningMessage) {
			alert(zenarioO.stopWarningMessage);
		}
		return false;
	}
	
	var branchLevel = zenarioO.branches.length - 1,
		stats = {},
		canGoBack = zenarioO.getFromLastPanel(branchLevel, zenarioO.path, 'title', false, times, false, stats),
		backwards = {},
		i;
	
	if (canGoBack !== false) {
		//Try to get the previously selected item from the previous panel, if there was one
		backwards.selectedItemFromLastPanel = zenarioO.getSelectedItemFromLastPanel(stats.path);
		
		//Deslect all items before leaving
		zenarioO.deselectAllItems();
		zenarioO.clearRefiner();
		zenarioO.clearSearch();
		
		for (i = stats.pops; i > 0; --i) {
			zenarioO.branches.pop();
		}
		zenarioO.go(stats.path, undefined, undefined, undefined, undefined, backwards);
	}
};




//Scan the map, registering any link that's a branch
zenarioO.knownBranches = {};
zenarioO.lookForBranches = function(map, path, panelPath, parentKey) {
	
	var isBranch, p, i, link;
	
	if (map._path_here) {
		panelPath = map._path_here;
	}
	
	foreach (map as i) {
		if (map[i] && typeof map[i] == 'object') {
			
			p = path? path + '/' + i : i;
			
			//Old logic: developers must manually specify which things are branches
			//var isBranch = map[i].link && typeof map[i].link == 'object' && map[i].link.branch;
			
			//New logic: all links are branches except for top/second-level items,
			//and top-level items may not contain links
			isBranch = false;
			if (parentKey !== undefined
			 && (link = map[i].link)
			 && (typeof link == 'object')) {
				 
				if (parentKey != 'nav') {
					link.branch = isBranch = true;
				
				} else {
					link.branch = isBranch = false;
					
					//Automatically turn links with refiners into deep links to that refiner
					if (link.refiner
					 && link.path
					 && link.path.indexOf('//') == -1) {
						
						link.path += '/refiners/' + link.refiner + '//';
						if (link.refinerId) {
							link.path += link.refinerId;
						}
						link.path += '//';
					}
				}
			}
			
			//Allow refiners to link to themselves, to save us from adding needless Waypoints just to link to refiners
			if (parentKey == 'refiners' && typeof map[i] == 'object' && map[i].link === undefined) {
				map[i].link = {
					path: panelPath,
					refiner: i
				};
				map[i].link.branch = isBranch = true;
			}
			
			if (isBranch) {
				if (!zenarioO.knownBranches[panelPath]) {
					zenarioO.knownBranches[panelPath] = {};
				}
				if (!zenarioO.knownBranches[panelPath][map[i].link.path]) {
					zenarioO.knownBranches[panelPath][map[i].link.path] = {};
				}
				
				//In the event that two branches go to the same place, prefer item double-click links over anything else
				if (!zenarioO.knownBranches[panelPath][map[i].link.path][ifNull(map[i].link.refiner, 1)]
				 || ('' + zenarioO.knownBranches[panelPath][map[i].link.path][ifNull(map[i].link.refiner, 1)]).substr(-4) != 'item') {
					//Otherwise, log the branch and stop scanning the parent tag
					zenarioO.knownBranches[panelPath][map[i].link.path][ifNull(map[i].link.refiner, 1)] = zenarioO.shortenPath(p);
				}
			}
			
			zenarioO.lookForBranches(map[i], p, panelPath, i);
		}
	}
};

//Remove every branch taken so far from memory
zenarioO.resetBranches = function() {
	zenarioO.branches = [{
		'panel_instances': {},
		'bypasses': {},
		'filters': {},
		'refiners': {},
		'searches': {}
	}];
};
zenarioO.resetBranches();

//Add a new branch
zenarioO.branch = function(path, lastPath, lastTitle, noReturnEnabled) {
	
	path = zenarioO.shortenPath(path);
	
	zenarioO.branches[zenarioO.branches.length] = {
		'from': lastPath,
		'to': path,
		'title': lastTitle,
		'no_return': noReturnEnabled,
		'panel_instances': {},
		'bypasses': {},
		'filters': {},
		'refiners': {},
		'searches': {}
	};
};

//Set the current location in the URL
zenarioO.setHash = function() {
	
	//Don't bother setting a hash in select mode, where you can't see the path anyway
	if (window.zenarioONotFull) {
		return;
	}
	
	//Don't set the location in the hash in IE 6/7, as this really slows them down
	if (!zenario.browserIsIE(7)) {
		var path = zenarioO.getHash();
		
		//Add the path to the hash
		document.location.hash = path;
		zenario.currentHash = document.location.hash;
	}
	
	if (zenarioA.homeLink && get('home_page_button_link')) {
		get('home_page_button_link').href = zenarioO.parseReturnLink(zenarioA.homeLink);
	}
	if (zenarioA.backLink && get('last_page_button_link')) {
		get('last_page_button_link').href = zenarioO.parseReturnLink(zenarioA.backLink);
	}
};


zenarioO.getHash = function(ignoreSelectedItem) {
	
	var hash = '',
		path = zenarioO.path,
		b, kb, lastTo, failed = false,
		oneItemSelected = false,
		selectedItem = '',
		selectedItems = zenarioO.pi && zenarioO.pi.returnSelectedItems(),
		prevPanels, prevPanelInstance, prevSelectedItems;
	
	if (!ignoreSelectedItem && selectedItems) {
		//Check if there was only one item selected
		foreach (selectedItems as var i) {
			if (selectedItems[i]) {
				if (oneItemSelected === false) {
					oneItemSelected = i;
				} else {
					oneItemSelected = false;
					break;
				}
			}
		}
		
		//If there is one item selected, put it in the hash after the path
		if (oneItemSelected) {
			if (!zenarioO.inspectionView) {
				selectedItem = oneItemSelected;
			} else {
				selectedItem = oneItemSelected + '/';
			}
		}
	}
	
	//Attempt to include the current branches taken in the URL by creating a deep link
	foreach (zenarioO.branches as var i) {
		if (i > 0) {
			b = zenarioO.branches[i];
			
			//Is this branch in the map?
			if (b.to && zenarioO.knownBranches[b.from] && zenarioO.knownBranches[b.from][b.to]
			 && (kb = zenarioO.knownBranches[b.from][b.to][b.refiners[b.to]? b.refiners[b.to].name : 1])) {
				
				kb = zenarioO.shortenPath(kb);
				
				if (lastTo) {
					//Use a relative path on the other end of the branch, to save space in the URL
					if (kb.substr(0, lastTo.length) == lastTo) {
						kb = zenarioO.shortenPath(kb.substr(lastTo.length + 1));
					} else {
						//If we couldn't get a relative path then there was something odd with the branch
						//and we cannot create a deep link to it
						failed = true;
						break;
					}
					hash += '//';
				}
				
				//Add any selected items to the deep link
				hash += kb + '//';
				
				prevPanels = zenarioO.branches[i-1];
				if (prevPanelInstance = prevPanels.panel_instances[b.from]) {
					hash += zenarioO.implodeKeys(prevPanelInstance.returnSelectedItems());
				}
				
				lastTo = b.to;
			} else {
				//If the branch wasn't registered then we can't create a deep link to it
				//Note that this should never happen, but if it does break I don't want Organizer breaking too
				failed = true;
				break;
			}
		}
	}
	
	//If we successfully built a deep link, combine it with the current path
		//Note that this involves running some of the above logic again
	if (!failed && lastTo) {
		//Use a relative path on the other end of the branch, to save space in the URL
		if (path.substr(0, lastTo.length) == lastTo) {
			path = hash + '//' + zenarioO.shortenPath(path.substr(lastTo.length + 1));
		} else {
			//If we couldn't get a relative path then there was something odd with the branch
			//and we cannot create a deep link to it
		}
	}
	
	//Add the selected item to the URL
	if (selectedItem) {
		//No matter what the generated path was, there should always be two slashes between the selected item and the path
		if (zenario.rightHandedSubStr(path, 2) == '//') {
			path += selectedItem;
		} else if (zenario.rightHandedSubStr(path, 1) == '/') {
			path += '/' + selectedItem;
		} else {
			path += '//' + selectedItem;
		}
	}
	
	if (!ignoreSelectedItem
	 && zenarioO.searchTerm !== undefined) {
		path += '~-' + encodeURIComponent(zenarioO.searchTerm);
	}
	if (!ignoreSelectedItem
	 && zenarioO.path
	 && zenarioO.branches.length
	 && zenarioO.branches[zenarioO.branches.length-1]
	 && zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path]
	 && !_.isEmpty(zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path])) {
		path += '~_' + decodeURI(encodeURIComponent(JSON.stringify(zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path])));
	}
	
	return path;
};



zenarioO.searchOnClick = function(el) {
};

//Update the search term
zenarioO.searchOnKeyUp = function(el) {
	if (zenarioO.doSearchAfterDelay) {
		clearTimeout(zenarioO.doSearchAfterDelay);
	}
	
	zenarioO.doSearchAfterDelay =
		setTimeout(
			function() {
				zenarioO.doSearch(get('organizer_search_term'));
			}, zenarioO.searchDelayTime);
};

zenarioO.searchOnChange = function(el) {
	if (zenarioO.server_side) {
		if (zenarioO.doSearchAfterDelay) {
			clearTimeout(zenarioO.doSearchAfterDelay);
			zenarioO.doSearch(el);
		}
	}
};

zenarioO.markIfViewIsFiltered = function() {
	zenarioO.filteredView = zenarioO.searchTerm !== undefined || zenarioO.filtersSet;
};

zenarioO.doSearch = function(el) {
	if (zenarioO.doSearchAfterDelay) {
		clearTimeout(zenarioO.doSearchAfterDelay);
	}
	
	var searchTerm;
	if (el.value === '') {
		searchTerm = undefined;
	} else {
		searchTerm = el.value;
		
		//Ensure that the searct term is numeric if possible
		if (searchTerm.substr(0, 1) !== '0'
		 && searchTerm == 1*searchTerm) {
			searchTerm = 1*searchTerm;
		}
	}
	
	//Don't launch a search if the search term has not changed
	if (searchTerm === zenarioO.searchTerm) {
		return;
	}
	
	zenarioO.searchTerm = searchTerm;
	zenarioO.markIfViewIsFiltered();
	
	zenarioO.saveSearch(zenarioO.searchTerm);
	zenarioO.runSearch();
};

zenarioO.runSearch = function() {
	if (zenarioO.stop) {
		return false;
	}
	
	zenarioO.lockPageClicks = true;
	zenarioO.inspectionView = false;
	
	//This slightly confusing line tries to allow the panel instance to turn on server side mode on the fly
	//and not have organizer break!
	if (zenarioO.server_side || (zenarioO.server_side = zenarioO.pi.returnDoSortingAndSearchingOnServer())) {
		zenarioO.refreshAndShowPage();
	} else {
		zenarioO.searchAndSortItems(zenarioO.searchTerm);
	}
};




zenarioO.allItemsSelected = function() {
	if (zenarioO.shownItemsLength == 0) {
		return false;
	}
	
	var i,
		c = 0,
		selectedItems = zenarioO.pi.returnSelectedItems();
	
	foreach (selectedItems as i) {
		++c;
	}
	
	return c == zenarioO.shownItemsLength;
};

zenarioO.noItemsSelected = function() {
	if (zenarioO.shownItemsLength == 0) {
		return true;
	}
	
	var i,
		selectedItems = zenarioO.pi.returnSelectedItems();
	
	foreach (selectedItems as i) {
		return false;
	}
	
	return true;
};






zenarioO.parseReturnLink = function(url, replace) {
	
	if (replace === undefined) {
		if (url && zenario.currentHash && !zenario.browserIsIE(7)) {
			replace = 'zenario_sk_return=' + zenario.currentHash.replace('#', '');
		} else {
			replace = '';
		}
	}
	
	return zenario.addBasePath(('' + url).replace('zenario_sk_return=navigation_path', replace));
};

//Remember a refiner on the current panel
zenarioO.saveRefiner = function(refiner) {
	zenarioO.branches[zenarioO.branches.length-1].refiners[zenarioO.path] = zenarioO.refiner = refiner;
};

//Check to see if there was a refiner on a panel
zenarioO.loadRefiner = function(path, branch) {
	//New branch? No refiner if so.
	if (branch === -1 || branch) {
		return undefined;
	}
	
	if (zenarioO.branches[zenarioO.branches.length-1].refiners[path]) {
		return zenarioO.branches[zenarioO.branches.length-1].refiners[path];
	}
	
	return undefined;
};

zenarioO.clearRefiner = function() {
	zenarioO.refiner = undefined;
	zenarioO.saveRefiner();
};

//Remember an admin's search
zenarioO.saveSearch = function(searchTerm) {
	zenarioO.branches[zenarioO.branches.length-1].searches[zenarioO.path] = searchTerm;
	zenarioO.pi.cmsSetsSearchTerm(searchTerm);
};

//Check to see if there was a Search on a panel
zenarioO.loadFromBranches = function(path, branch, type) {
	//New branch? Clear the search if so.
	if (branch === -1 || branch) {
		return undefined;
	}
	
	if (zenarioO.branches[zenarioO.branches.length-1]
	 && zenarioO.branches[zenarioO.branches.length-1][type]
	 && zenarioO.branches[zenarioO.branches.length-1][type][path]) {
		return zenarioO.branches[zenarioO.branches.length-1][type][path];
	}
	
	return undefined;
};

zenarioO.setSearch = function(searchTerm) {
	
	var visibility;
	
	if (zenarioO.pi && zenarioO.pi.returnSearchingEnabled()) {
		visibility = 'visible';
		zenarioO.searchTerm = searchTerm;
	} else {
		visibility = 'hidden';
		zenarioO.searchTerm = undefined;
	}
	
	zenarioO.markIfViewIsFiltered();
	get('organizer_search').style.visibility = visibility;
	
	if (zenarioO.searchTerm === undefined) {
		get('organizer_search_term').value = '';
	} else {
		get('organizer_search_term').value = zenarioO.searchTerm;
	}
};

zenarioO.clearSearch = function() {
	get('organizer_search_term').value = '';
	
	zenarioO.searchTerm = undefined;
	zenarioO.markIfViewIsFiltered();
	
	zenarioO.saveSearch(zenarioO.searchTerm);
};






zenarioO.toggleAllItems = function() {
	if (zenarioO.allItemsSelected()) {
		zenarioO.deselectAllItems();
	} else {
		zenarioO.selectAllItems();
	}
	zenarioO.setButtons();
	zenarioO.setHash();
};

zenarioO.selectAllItems = function() {
	zenarioO.selectItemRange();
};

zenarioO.deselectAllItems = function() {
	zenarioO.selectItemRange(-1);
};



zenarioO.selectItemRange = function(start, stop) {
	
	var changes = false,
		selectThisItem,
		inRange = start === undefined,
		selectedItems = zenarioO.pi.returnSelectedItems();
	
	for (var itemNo in zenarioO.searchedItems) {
		var id = zenarioO.searchedItems[itemNo];
		
		if (zenarioO.shownItems && zenarioO.shownItems[id]) {
			
			selectThisItem = inRange || id === start || id === stop;
			
			if (selectThisItem && !selectedItems[id]) {
				zenarioO.pi.selectItem(id);
				changes = true;
			
			} else if (!selectThisItem && selectedItems[id]) {
				zenarioO.pi.deselectItem(id);
				changes = true;
			}
			
			if ((id === start && id === stop)) {
				inRange = false;
			} else if (id === start || id === stop) {
				inRange = !inRange;
			}
		}
	}
	
	if (changes) {
		zenarioO.closeInspectionView();
	}
};


zenarioO.selectItems = function(itemsIn) {
	
	var i, id, selectItems = {},
		changes = false,
		selectThisItem,
		inRange = start === undefined,
		selectedItems = zenarioO.pi.returnSelectedItems();
	
	if (_.isArray(itemsIn)) {
		foreach (itemsIn as i => id) {
			selectItems[id] = true;
		}
	} else if (_.isObject(itemsIn)) {
		selectItems = itemsIn;
	} else {
		selectItems[itemsIn] = true;
	}
	
	foreach (zenarioO.shownItems as id) {
		selectThisItem = selectItems[id];
		
		if (selectThisItem && !selectedItems[id]) {
			zenarioO.pi.selectItem(id);
			changes = true;
		
		} else if (!selectThisItem && selectedItems[id]) {
			zenarioO.pi.deselectItem(id);
			changes = true;
		}
	}
	
	if (changes) {
		zenarioO.closeInspectionView();
		zenarioO.setButtons();
		zenarioO.setHash();
	}
};






zenarioO.itemClickThrough = function(id, e) {
	if (zenarioO.stop) {
		return false;
	}
	
	zenario.stop(e);
	
	
	if (!zenarioO.tuix
	 || !zenarioO.tuix.items
	 || !zenarioO.tuix.items[id]) {
		return false;
	}
	
	zenarioO.itemClickThroughAction(id);
	
	return false;
};

zenarioO.itemClickThroughAction = function(id) {
	var link = zenarioO.itemClickThroughLink(id),
		selectedItems;
	
	if (link) {
		if (!engToBoolean(link.unselect_items)) {
			selectedItems = {};
			selectedItems[id] = true;
			zenarioO.pi.cmsSetsSelectedItems(selectedItems);
			//zenarioO.saveSelection();
		}

		zenarioA.action(zenarioO, zenarioO.tuix.items[id], true, true, link);
		return true;
	} else {
		return false;
	}
};

zenarioO.itemClickThroughLink = function(id) {
	var link = zenarioO.tuix.items[id].link,
		panel = zenarioO.tuix.items[id].panel;
	
	if (link === undefined
	 && panel === undefined
	 && zenarioO.tuix.item) {
		link = zenarioO.tuix.item.link;
		panel = zenarioO.tuix.item.panel;
	}
	
	if (panel) {
		link = {path: panel._path_here};
	}
	
	//If there is a max path set, don't allow the administrator to navigate to a different path.
	//But if the disallow_refiners_looping_on_min_path propery is set, don't allow
	//the administrator to navigate regardless of whether the path is different	.
	if (link
	 && window.zenarioONotFull
	 && zenarioO.path === window.zenarioOMaxPath
	 && (zenarioO.path !== link.path || window.zenarioODisallowRefinersLoopingOnMinPath)) {
		return false;
	}
	
	return link;
};





zenarioO.topRightButtonClick = function(id) {
	if (zenarioO.stop) {
		return false;
	}
	
	zenarioA.action(zenarioO, zenarioO.map.top_right_buttons[id], false, -1);
};

zenarioO.topLevelClick = function(id, j, first) {
	if (zenarioO.stop) {
		return false;
	}
	
	zenarioO.clearRefiner();
	zenarioO.clearSearch();
	zenarioO.resetBranches();
	
	if (j !== undefined) {
		var obj = $.extend(true, {}, zenarioO.map[id].nav[j]);
		
		zenarioA.action(zenarioO, obj, false, -1);
	} else {
		zenarioA.action(zenarioO, zenarioO.map[id], false, -1);
	}
};

zenarioO.viewTrash = function () {
	if (!window.zenarioOSelectMode && zenarioO.tuix && zenarioO.tuix.trash && !engToBoolean(zenarioO.tuix.trash.empty)) {
		zenarioA.action(zenarioO, zenarioO.tuix.trash, false, true);
	}
	
	return false;
};

zenarioO.collectionButtonClick = function(id) {
	if (zenarioO.tuix && zenarioO.tuix.collection_buttons && zenarioO.tuix.collection_buttons[id]) {
		zenarioA.action(zenarioO, zenarioO.tuix.collection_buttons[id], false, true);
	}
};

zenarioO.itemButtonClick = function(id, itemId) {
	if (zenarioO.tuix && zenarioO.tuix.item_buttons && zenarioO.tuix.item_buttons[id]) {
		if (itemId !== undefined) {
			zenarioO.selectItems(itemId);
		}
		zenarioA.action(zenarioO, zenarioO.tuix.item_buttons[id], true, true);
	}
};

zenarioO.inlineButtonClick = function(id, itemId) {
	if (zenarioO.tuix && zenarioO.tuix.inline_buttons && zenarioO.tuix.inline_buttons[id]) {
		zenarioO.selectItems(itemId);
		zenarioA.action(zenarioO, zenarioO.tuix.inline_buttons[id], true, true);
	}
};

zenarioO.toggleQuickFilter = function(id, turnOn) {

	var c, i, otherButton, filterType,
		changedSomething = false,
		quick_filter_buttons =
			zenarioO.tuix
		 && zenarioO.tuix.quick_filter_buttons
		button =
			quick_filter_buttons
		 && quick_filter_buttons[id];
	
	if (!button) {
		return;
	}

	//Clicking on a quick-filter-button with clear_all set will clear every
	//other quick-filter button. (But if this is in a drop-down, only the buttons
	//in the drop-down will be affected.)
	if (engToBoolean(button.clear_all)) {
		
		foreach (quick_filter_buttons as i => otherButton) {
			
			//Ignore this button itself!
			if (i == id) {
				continue;
			}
			
			if (!button.parent
			 || button.parent == i
			 || button.parent == otherButton.parent) {
				if (c = otherButton.column) {
					if (zenarioO.getFilterValue('s', c)) {
						zenarioO.setFilterValue('s', c, false);
						zenarioO.clearFilter(c);
						changedSomething = true;
					}
				}
			}
		}
	}
	
	//If this quick-filter-button has a column set, try to either
	//apply a filter to that column, or else turn off the filter if
	//it was previously applied
	if ((c = button.column)
	 && (filterType = zenarioO.getColumnFilterType(c))
	 && (zenarioO.checkIfColumnPickerChangesAreAllowed(c))) {
	
		//Work out whether this button should be enabled
		if (engToBoolean(button.remove_filter)) {
			turnOn = false;
	
		} else if (turnOn === undefined) {
			turnOn = !zenarioO.quickFilterEnabled(id);
		}
	
		zenarioO.setFilterValue('s', c, turnOn);
		zenarioO.clearFilter(c);
	
		if (turnOn) {
			zenarioO.setFilterValue('not', c, engToBoolean(button.invert));
	
			if (filterType == 'yes_or_no') {
				zenarioO.setFilterValue('v', c, 1);
	
			} else if (button.value !== undefined) {
				zenarioO.setFilterValue('v', c, button.value);
			}
		}
		
		changedSomething = true;
	}
	
	if (changedSomething) {
		//Refresh to update the panel with the changes
		zenarioO.refreshAndShowPage();
	}
};

//Check whether a specific quick-filter button is enabled
zenarioO.quickFilterEnabled = function(id) {

	var filter,
		c,
		filterType;

	if ((filter = zenarioO.tuix.quick_filter_buttons[id])
	 && (c = filter.column)
	 && (filterType = zenarioO.getColumnFilterType(c))) {
		
		if (engToBoolean(filter.remove_filter)) {
			return !zenarioO.getFilterValue('s', c);
		
		} else {
			if (zenarioO.getFilterValue('s', c)
			 && engToBoolean(filter.invert) == engToBoolean(zenarioO.getFilterValue('not', c))) {
				
				if (filterType == 'yes_or_no') {
					return true;
	
				} else if (filter.value !== undefined) {
					return zenarioO.getFilterValue('v', c) === filter.value;
				}
			}
		}
	}
	
	return false;
};



//Apply merge fields to a message, using the currently selected item's columns as fields
zenarioO.applyMergeFields = function(string, escapeHTML, i, keepNewLines) {
	
	//Escape any ~s in the string
	string = string.replace(/~/g, '~1');
	
	var string2 = string,
		columnValue,
		c, v;
	
	if (i === undefined) {
		i = zenarioO.getKeyId();
	}
	
	foreach (zenarioO.tuix.items[i] as c => v) {
		if (string.indexOf('[[' + c + ']]') != -1) {
			
			if (zenarioO.tuix.columns
			 && zenarioO.tuix.columns[c]) {
				columnValue = zenarioO.columnValue(i, c, !escapeHTML);
			
			} else if (escapeHTML) {
				columnValue = htmlspecialchars(v);
			
			} else {
				columnValue = v;
			}
			
			if (!keepNewLines && columnValue !== undefined) {
				columnValue = columnValue.replace(/\n/g, ' ');
			}
			
			//Escape any ~s, [s and ]s in the string
			columnValue = columnValue.replace(/~/g, '~1').replace(/\[/g, '~2').replace(/\]/g, '~3');
			
			while (string != (string2 = string.replace('[[' + c + ']]', columnValue))) {
				string = string2;
			}
		}
	}
	
	//Unescape and return
	return string.replace(/~2/g, '[').replace(/~3/g, '[').replace(/~1/g, '~');
};

zenarioO.applyMergeFieldsToLabel = function(label, isHTML, itemLevel, multiSelectLabel) {
	
	if (itemLevel
	 && multiSelectLabel
	 && zenarioO.itemsSelected > 1) {
		label = multiSelectLabel;
	}
	
	if (!label) {
		return label;
	}
	
	label = ('' + label).replace(/\[\[item_count\]\]/ig, zenarioO.itemsSelected);

	if (itemLevel
	 && zenarioO.itemsSelected == 1) {
		label = zenarioO.applyMergeFields(label, isHTML);
	}
	
	return label;
};


//Given a long piece of text that doesn't wrap, attempt to add tiny spaces to force it to wrap at certain points
zenarioO.applySmallSpaces = function(text) {
	var pos, piece, snipTo, out = '', wordLength = 25;
	
	//Firstly, do our best to spit between existing words, and anything else that would wrap normally
	while ((pos = text.search(/([a-z][A-Z]|[a-zA-Z][^a-zA-Z\)]|[^a-zA-Z\(][a-zA-Z]|[0-9][^0-9\)]|[^0-9\(][0-9])/)) != -1) {
		
		//Spilt the text up into each piece.
		//Where we spilt by a space, include the space as part of the piece before.
		//If we split by something that wasn't a space, keep the non-space as part of the word after
		if (text[pos + 1].match(/\s/) !== null) {
			snipTo = pos + 2;
		} else {
			snipTo = pos + 1;
		}
		
		piece = text.substr(0, snipTo);
		text = text.substr(snipTo);
		
		//If this word is longer than the max length, forcible split it up.
		out += zenarioO.maxLengthString(htmlspecialchars(piece, false, 'asis'), wordLength);
		
		//If there was no space in this piece, add one
		if (piece.search(/\s/) == -1) {
			out += '<span style="font-size: 1px;"> </span>';
		}
	}
	
	//If the last piece is longer than the max length, forcibly split it up.
	out += zenarioO.maxLengthString(htmlspecialchars(text), wordLength);
	
	return out;
};

zenarioO.maxLengthString = function(text, wordLength) {
	var out = '';
	while (text.wordLength > wordLength) {
		out += text.substr(0, wordLength) + '<span style="font-size: 1px;"> </span>';
		text = text.substr(wordLength);
	}
	
	out += text;
	
	return out;
};

zenarioO.pickItems = function(path, keyIn, row) {
	
	//Remove any "parent__" paramaters from the key, which are going to be just dupicated here
	var key = {};
	foreach (keyIn as var i) {
		if (i == 'id') {
			key[i] = keyIn[i];
		} else if (i.substr(0, 8) != 'parent__') {
			key['child__' + i] = keyIn[i];
		}
	}
	
	if (zenarioO.postPickItemsObject) {
		if (zenarioO.pickItemsItemLevel) {
			key.id2 = key.id;
			delete key.id;
		}
		zenarioA.action(zenarioO, zenarioO.postPickItemsObject, true, true, undefined, key);
	
	} else if (zenarioO.actionTarget) {
		if (zenarioO.pickItemsItemLevel) {
			zenarioO.actionRequests.id2 = key.id;
			foreach (key as var k) {
				if (k != 'id') {
					zenarioO.actionRequests[k] = key[k];
				}
			}
		} else {
			foreach (key as var k) {
				zenarioO.actionRequests[k] = key[k];
			}
		}
		
		zenarioO.action2();
	}
	
	zenarioO.postPickItemsObject = false;
};

zenarioO.action2 = function() {
	if (zenarioO.actionTarget) {
		//Number each request that is made, so we can tell which ones are outdated
		var goNum = ++zenarioO.goNum;
		get('organizer_preloader_circle').style.display = 'block';
		
		zenario.ajax(URLBasePath + zenarioO.actionTarget, zenarioO.actionRequests, false, false, true).after(function(message) {
			//Check that this isn't an out-of-date request that has come in syncronously via AJAX
			if (goNum != zenarioO.goNum) {
				return;
			}

			if (message) {
				if (zenarioA.showMessage(message, undefined, 'error') === false) {
					get('organizer_preloader_circle').style.display = 'none';
					return;
				}
			}
			
			zenarioO.selectCreatedIds();
		});
	}
	zenarioO.actionTarget = false;
	delete zenarioO.actionRequests;
};

zenarioO.uploadStart = function() {
	zenarioO.disableInteraction();
};

zenarioO.uploadComplete = function() {
	zenarioO.enableInteraction();
	zenarioO.selectCreatedIds();
};

zenarioO.selectCreatedIds = function() {
	zenario.ajax(URLBasePath + 'zenario/ajax.php?method_call=getNewId', true, true).after(function(newIds) {
		zenarioO.refreshToShowItem(newIds);
	});
};


//If the admin manually presses the reload button in Organizer, clear the cache and reload the panel.
zenarioO.reloadButton = function() {
	zenario.outdateCachedData();
	zenarioO.reload();
};

//Refresh the current view
zenarioO.lastActivity = false;
zenarioO.refreshIsPeriodic = false;
zenarioO.reload = function(periodic, allowCache, runFunctionAfter) {
	
	//Stop doing periodic refreshes after 20 minutes if inactivity
	if (periodic) {
		if (!zenarioO.lastActivity || ((Date.now() - zenarioO.lastActivity) > 20 * 60 * 1000)) {
			return false;
		}
	}
	
	//(Periodic refresh is now disabled)
	////Don't allow a periodic refresh when uploading, or when the column picker is open, or when resizing/sorting columns
	//if (zenarioA.uploading
	// || (periodic
	//  && (zenarioO.sortingColumn
	//   || zenarioO.resizingColumn
	//   || zenarioA.checkIfBoxIsOpen('AdminColumnFilter')
	//   || zenarioA.checkIfBoxIsOpen('AdminViewModeOptions')))) {
	//	
	//	//However still keep the timer going incase this it is later closed
	//	zenarioO.periodicRefresh();
	//	
	//	return false;
	//}
	
	zenarioO.stopRefreshing();
	
	//In hierarchy mode, remember what was open/closed when refreshing
	//zenarioO.recordOpenItems();
	//zenarioO.saveOpenItems();
	
	
	var path = zenarioO.path,
		itemToSelect = zenarioO.getKeyId(true),
		inCloseUpView = zenarioO.inspectionView;
	
	if (itemToSelect) {
		zenarioO.refreshToPage = undefined;
	} else {
		zenarioO.refreshToPage = zenarioO.page;
	}
	zenarioO.refreshIsPeriodic = periodic;
	
	
	//zenarioO.go = function(path, branch, refiner, queued, lastInQueue, backwards, dontUseCache, itemToSelect, sameLoad, runFunctionAfter, periodic, inCloseUpView) {
	zenarioO.go(path, undefined, undefined, undefined, undefined, undefined, !allowCache, itemToSelect, undefined, runFunctionAfter, periodic, inCloseUpView);
};

//zenarioO.load() does the same thing as zenarioO.reload(), except it uses the cache if possible
zenarioO.load = function() {
	zenarioO.reload(false, true);
};

//Go to the change password section
zenarioO.changePassword = function() {
	zenarioO.reloadPage(undefined, true, 'change_password');
};

//Reload Organizer, making sure to go via the admin login page in case a login/db_update is needed
zenarioO.reloadPage =
zenarioO.refreshPage = function(hash, dontAutoDetectMode, task) {
	if (zenarioO.stop) {
		return false;
	}

	task = task || '';
	
	if (hash === undefined) {
		if (zenario.currentHash && !zenario.browserIsIE(7)) {
			hash = zenario.currentHash.replace('#', '');
		} else {
			hash = zenarioO.getHash();
		}
	}
	
	if (!task && !dontAutoDetectMode) {
		if (hash == 'zenario__administration/panels/site_settings//site_reset') {
			task = 'site_reset';
		
		} else if (hash.substr(0, 31) == 'zenario__administration/panels/backups') {
			task = 'restore';
		
		} else {
			task = 'reload_sk';
		}
	}

	window.location.href =
		URLBasePath +
		'zenario/admin/welcome.php?task=' + task + '&og=' + encodeURIComponent(hash) +
		(zenarioA.fromCID? '&fromCID=' + zenarioA.fromCID + '&fromCType=' + zenarioA.fromCType : '');
};

//Refresh the panel to show an item after a button click or admin box is saved.
zenarioO.refreshToShowItem = function(itemId, growlIfItemIsVisible, growlIfItemIsNotVisible) {
	if (zenarioO.stop) {
		return false;
	}
	
	//If this is the admin or the site settings panel, do a SK reload instead
	if (zenarioO.path == 'zenario__administration/panels/site_settings') {
		zenarioO.reloadPage();
		return;
	}
	
	var i, itemIds,
		path = zenarioO.path,
		selectedItems;
	
	//Normally, when refreshing to show one item, the system checks to
	//see what page that item is on, and specifically shows that page.
	//But If there are no items or multiple items selected,
	//then the system won't be able to use this logic.
	//So as a fallback, we'll try and stay on the current page.
	if (zenarioO.page) {
		zenarioO.refreshToPage = zenarioO.page;
	}
	
	//T10250, When deleting an item in Organizer, select the next one
	//If an item or items are currently selected, remember which item is next in
	//the list after them for use in our logic later.
	zenarioO.selectNextItem = zenarioO.getNextItem();
	
	
	//Accept a string, array or object as an input
	if (itemId === false
	 || itemId === undefined) {
		itemIds = [];
	
	} else if (_.isObject(itemId)) {
		itemIds = _.keys(itemId);
	
	} else if (_.isArray(itemId)) {
		itemIds = itemId;
	
	} else {
		itemIds = (itemId + '').split(',');
	}
	
	
	//If any items were specified, use these instead of the currently selected items.
	//(If no items were specificed, keep using the currently selected items.)
	if (itemIds.length) {
		selectedItems = {};
		foreach (itemIds as i) {
	
			//Escape the item id if needed
			if (zenarioO.tuix.db_items
			 && zenarioO.tuix.db_items.encode_id_column) {
				itemIds[i] = zenario.encodeItemIdForOrganizer(itemIds[i]);
			}
			
			selectedItems[itemIds[i]] = true;
		}
		
		zenarioO.pi.cmsSetsSelectedItems(selectedItems);
		zenarioO.setHash();
		
		path += '//' + itemIds.join(',');
		if (zenarioO.inspectionView) {
			path += '/';
		}
	}
	
	zenarioO.stopRefreshing();
	
	//Clear the local storage, as there have probably just been changes
	zenario.outdateCachedData();
	
	//zenarioO.go = function(path, branch, refiner, queued, lastInQueue, backwards, dontUseCache, itemToSelect, sameLoad, runFunctionAfter, periodic, inCloseUpView) {
	zenarioO.go(path, undefined, undefined, undefined, undefined, undefined, undefined, undefined, undefined, function() {
		
		var name;
		
		if (itemId
		 && (itemId + '').indexOf(',') == -1
		 && zenarioO.pi
		 && zenarioO.tuix) {
			
			
			
			if (growlIfItemIsVisible
			 && (name = zenarioA.formatOrganizerItemName(zenarioO.tuix, itemId))) {
				zenarioO.pi.displayToastMessage(growlIfItemIsVisible.replace(/\[\[name\]\]/ig, htmlspecialchars(name)));
			}
			
			//If an item was just saved, but isn't visible, show the "Item saved, but your filter prevents it from appearing" message
			if (growlIfItemIsNotVisible
			 && !(zenarioO.tuix.items && zenarioO.tuix.items[itemId])) {
				
				//Quickly check if the item still exists; e.g. we shouldn't show this for deleted items
				zenarioA.getItemFromOrganizer(zenarioO.path, itemId, true).after(data => {
					
					//Show the message if it exists
					if (data.items
					 && data.items[itemId]) {
						zenarioO.pi.displayToastMessage(growlIfItemIsNotVisible);
					}
				});
			}
		}
	});
};

//Keep refreshing panels that can change
zenarioO.refreshing = false;

//(Periodic refresh is now disabled)
//zenarioO.periodicRefresh = function() {
//	if (zenarioO.stop) {
//		return false;
//	}
//	
//	zenarioO.stopRefreshing();
//	zenarioO.refreshing =
//		setTimeout(
//			function() {
//				zenarioO.reload(true, true);
//			}, zenarioO.periodicRefreshTime);
//}

zenarioO.stopRefreshing = function() {
	if (zenarioO.refreshing) {
		clearTimeout(zenarioO.refreshing);
	}
};




//WiP CSV export ability
zenarioO.doCSVExport = function() {
	zenarioO.CSVExport = true;
	zenarioO.reload();
};


zenarioO.getNextItem = function() {
	
	var id, ord,
		nextId,
		nextOrd = -1,
		item_sort_order,
		selectedItems;
	
	if (!(item_sort_order = zenarioO.tuix && zenarioO.searchedItems)
	 || !(selectedItems = zenarioO.pi && zenarioO.pi.returnSelectedItems())) {
		return false;
	}
	
	foreach (item_sort_order as ord => id) {
		ord = 1*ord + 1;
		if (ord > nextOrd
		 && selectedItems[id]
		 && (nextId = item_sort_order[ord])
		 && (!selectedItems[nextId])) {
			nextOrd = ord;
		}
	}
	
	return nextOrd != -1 && item_sort_order[nextOrd];
	
};



//Get the current collection key, as well as the current item id(s) if this is something on the item level
zenarioO.getKey = function(itemLevel) {
	
	var key = {};
	if (zenarioO.tuix.key) {
		foreach (zenarioO.tuix.key as var i) {
			key[i] = zenarioO.tuix.key[i];
		}
	}
	
	if (zenarioO.refiner) {
		key.refinerId = zenarioO.refiner.id;
		key.refinerName = zenarioO.refiner.name;
	}
	
	if (zenarioO.lastRefiners) {
		foreach (zenarioO.lastRefiners as var f) {
			key[f] = zenarioO.lastRefiners[f];
		}
	}
	
	if (itemLevel) {
		key.id = zenarioO.getKeyId();
	}
	
	if (zenarioA.openedInIframe && windowParent && windowParent.zenario) {
		if (windowParent.zenarioO.init
		 && windowParent.zenarioO.tuix) {
			var parentKey = windowParent.zenarioO.getKey(true);
			foreach (parentKey as var i) {
				key['parent__' + i] = parentKey[i];
			}
		} else if (windowParent.zenario.cID) {
			key.parent__cID = windowParent.zenario.cID;
			key.parent__cType = windowParent.zenario.cType;
			key.parent__cVersion = windowParent.zenario.cVersion;
		}
	}
	
	return key;
};

zenarioO.selectedItems = function() {
	return zenarioO.pi && zenarioO.pi.returnSelectedItems();
};

zenarioO.selectedItemId = function() {
	return zenarioO.getKeyId(true, true);
};

zenarioO.selectedItemIds = function() {
	return zenarioO.getKeyId();
};

zenarioO.selectedItemDetails = function() {
	var id = zenarioO.selectedItemId();
	return (id && zenarioO.tuix.items && zenarioO.tuix.items[id]) || {};
};

zenarioO.getKeyId = function(limitOfOne, onlyOne) {
	
	var thisId,
		id = '',
		comma = '',
		selectedItems = zenarioO.selectedItems();
	
	if (selectedItems) {
		foreach (selectedItems as thisId) {
			
			if (id === '') {
				id = thisId;
			
			} else {
				if (onlyOne) {
					return false;
				} else if (limitOfOne) {
					return id;
				} else {
					id += ',' + thisId;
				}
			}
		}
	}
	
	return id;
};

zenarioO.getLastKeyId = function(limitOfOne) {
	var refiner;
	if (refiner = zenarioO.loadRefiner(zenarioO.path)) {
		return refiner.id;
	} else {
		return false;
	}
};


//From a given path, get part of the map object
zenarioO.followPathOnMap = function(path, attribute, getLocation) {
	
	if (!path) {
		path = zenarioO.defaultPath;
	}
	
	var focus = zenarioO.map,
		split = path.split('/'),
		from = false,
		to = false;
	
	foreach (split as var i) {
		var tag = split[i];
		
		if (tag === '') {
			continue;
		}
		
		if (i == 0) {
			from = to = tag;
		}
		
		if (focus[tag]) {
			focus = focus[tag];
		} else {
			return false;
		}
		
		if (focus.link && focus.link.path) {
			from = to;
			to = focus.link.path;
		
		} else if (focus._path_here) {
			from = to;
			to = focus._path_here;
		}
	}
	
	//For second level items, add some code that can read an attribute off of the second level item rather than the containing panel.
	if (focus.panel && (getLocation || !attribute || (focus[attribute] === undefined && focus.panel[attribute] !== undefined))) {
		focus = focus.panel;
	}
	
	if (getLocation) {
		if (!focus.link) {
			return false;
		} else {
			return {from: from, to: to, branch: focus.link.branch, refiner: focus.link.refiner};
		}
	} else if (attribute) {
		return focus[attribute];
	} else {
		return focus;
	}
};

//Given a <link> tag, get where it came from and where it goes to
zenarioO.getFromToFromLink = function(path) {
	return zenarioO.followPathOnMap(path, false, true);
};




//  View Modes  //

zenarioO.showViewOptions = function(e) {
	if (zenarioO.stop) {
		return false;
	}
	
	if (!zenarioO.tuix.columns) {
		return false;
	}
	
	//Check if the prefs are up to date. If not, refresh the panel first.
	if (zenarioO.checkPrefs()) {
		zenarioO.showViewOptions2();
	} else {
		zenarioO.reload(undefined, undefined, zenarioO.showViewOptions2);
	}
	
	return false;
};

zenarioO.showViewOptions2 = function() {
	var width = 445,
		left = -395,
		top = -2;
	zenarioA.openBox(false, 'zenario_view_options', 'AdminViewModeOptions', get('organizer_listViewOptionsDropdown'), width, left, top, false, true, false, false, undefined, undefined, false, true);
	
	zenarioO.setViewOptions();
};

zenarioO.getColumnFilterType = function(c) {
	
	if (!zenarioO.tuix
	 || !zenarioO.tuix.columns
	 || !zenarioO.tuix.columns[c]) {
		return false
	}

	var filterType = false,
		filterFormat = zenarioO.tuix.columns[c].filter_format || zenarioO.tuix.columns[c].format;
	
	if (filterFormat == 'date'
	 || filterFormat == 'datetime'
	 || filterFormat == 'datetime_with_seconds') {
		filterType = 'date';
	
	} else if (filterFormat == 'yes_or_no') {
		filterType = 'yes_or_no';
	
	} else if (
		(filterFormat == 'enum' && zenarioO.tuix.columns[c].values)
	 || filterFormat == 'language_english_name_with_id'
	 || filterFormat == 'language_english_name'
	 || filterFormat == 'language_local_name_with_id'
	 || filterFormat == 'language_local_name'
	) {
		filterType = 'enum';
	
	} else if (engToBoolean(zenarioO.tuix.columns[c].searchable)) {
		filterType = 'search';
	}
	
	return filterType;
};

zenarioO.setViewOptions = function() {
	
	zenarioVO.shownTab = 'cp';
	zenarioVO.tuix = {
		tab: 'cp',
		tabs: {
			cp: {
				template: 'zenario_filters_popup',
				edit_mode: {
					on: true,
					enabled: true,
					always_on: true
				},
				fields: {}
			}
		}
	};
		//Backwards compatability for any old code
		//zenarioVO.focus = zenarioVO.tuix;	
	
	zenarioVO.tuix.tabs.cp.fields.showcol__title_ = {
		ord: -3,
		full_width: true,
		pre_field_html: '<div class="organizer_colPickerTitle_show" style="">',
		snippet: {html: phrase.show},
		post_field_html: '</div>'
	};
	
	zenarioVO.tuix.tabs.cp.fields.sortcol__title_ = {
		ord: -1,
		same_row: true,
		pre_field_html: '<div class="organizer_colPickerTitle_sort">',
		snippet: {html: phrase.sort},
		post_field_html: '</div>'
	};
	
	var c, colNo, column,
		lastCol = false,
		lastColName = false,
		prefs = zenarioO.prefs[zenarioO.path] || {};
	
	
	foreach (zenarioO.sortedColumns as colNo => c) {
		
		if ((column = zenarioO.tuix.columns[c])
		 && (zenarioO.isShowableColumn(c, false))) {
			
			zenarioVO.tuix.tabs.cp.fields['start_of_row__' + c] = {
				ord: 100 * colNo,
				full_width: true,
				snippet: {html: ''}
			}
			
			zenarioVO.tuix.tabs.cp.fields['showcol_' + c] = {
				ord: 100 * colNo + 4,
				same_row: true,
				type: 'toggle',
				onclick: "zenarioO.showHideColumn(!" + engToBoolean(zenarioO.shownColumns[c]) + ", '" + htmlspecialchars(c) + "'); zenarioO.setViewOptions();",
				value: zenarioO.shownColumns[c]? 'Hide' : 'Show',
				title: phrase.showCol,
				'class': (zenarioO.view_mode == 'grid' ||
						 engToBoolean(column.always_show)?
						 	'notused ' : '') + (zenarioO.shownColumns[c]? 'zen_col_hidden' : 'zen_col_shown')
			};
			
			if (zenarioO.showCSVInViewOptions) {
				delete zenarioVO.tuix.tabs.cp.fields['showcol_' + c].onclick;
				
				if (engToBoolean(engToBoolean(column.server_side_only))) {
					zenarioVO.tuix.tabs.cp.fields['showcol_' + c].style = 'visibility: hidden;';
				} else {
					zenarioVO.tuix.tabs.cp.fields['showcol_' + c].disabled = 'disabled';
				}
				
				var value = zenarioO.shownColumns[c];
				
				if (prefs.shownColumnsInCSV !== undefined
				 && prefs.shownColumnsInCSV[c] !== undefined) {
					value = prefs.shownColumnsInCSV[c];
				}
				
				zenarioVO.tuix.tabs.cp.fields['showcsv_' + c] = {
					ord: 100 * colNo + 5,
					same_row: true,
					type: 'checkbox',
					onclick: "zenarioO.showHideColumnInCSV(this, '" + htmlspecialchars(c) + "');",
					value: value,
					'class': zenarioO.view_mode == 'grid' ||
							 engToBoolean(column.always_show)?
								'notused' : ''
				};
			}
			
			zenarioVO.tuix.tabs.cp.fields['sortcol_' + c] = {
				ord: 100 * colNo + 6,
				same_row: true,
				snippet: {
					html: '<label class="zenario_filter_column_name" for="showcol_' + c + '">' + htmlspecialchars(column.title) + '</label>'
				}
			};
			
			if (zenarioO.canFilterColumn(c)) {
				
				var hidden = !zenarioO.getFilterValue('s', c),
					hiddenPreviously = hidden,
					value_ = zenarioO.getFilterValue('v', c),
					dates,
					dateBefore = '',
					dateAfter = '',
					filterFormat = column.filter_format || column.format,
					invertLink =
						'<a class="' + (zenarioO.getFilterValue('not', c)? 'organizer_inverter organizer_not' : 'organizer_inverter') + '"' +
							' title="' + phrase.invertFilter + '"' +
							' onclick="zenarioO.invertFilter(this, \'' + htmlspecialchars(c) + '\', \'' + htmlspecialchars(filterFormat) + '\');">';
				
				if (filterFormat == 'date'
				 || filterFormat == 'datetime'
				 || filterFormat == 'datetime_with_seconds') {
					
					if (value_) {
						dates = value_.split(',');
						dateBefore = dates[1];
						dateAfter = dates[0];
					}
					
					zenarioVO.tuix.tabs.cp.fields['date_after_col_' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_date_filters_for_field',
						label: '<span class="zenario_date_filters_phrase">' + phrase.after + '</span>',
						type: 'date',
						onchange: "zenarioO.updateDateFilters('" + htmlspecialchars(c) + "');",
						value: dateAfter,
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
					zenarioVO.tuix.tabs.cp.fields['date_before_col_' + c] = {
						ord: 100 * colNo + 8,
						row_class: 'zenario_date_filters_for_field',
						label: '<span class="zenario_date_filters_phrase">' + phrase.before + "</span>",
						type: 'date',
						onchange: "zenarioO.updateDateFilters('" + htmlspecialchars(c) + "');",
						value: dateBefore,
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
					zenarioVO.tuix.tabs.cp.fields['v' + c] = {
						ord: 100 * colNo + 9,
						same_row: true,
						type: 'hidden',
						value: value_,
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
				
				} else if (filterFormat == 'yes_or_no') {
					//Attempt to add a colon to the title of the column
					var label = ('' + column.title);
					if (label.indexOf(':') == -1) {
						label += ':';
						label = label.replace(/\s*:/, ':');
					}
					
					zenarioVO.tuix.tabs.cp.fields['v' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field yes_or_no',
						snippet: {
							html: invertLink + (zenarioO.getFilterValue('not', c)? phrase.no : phrase.yes) + '</a>'
						},
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
				
				} else if (
					(filterFormat == 'enum' && column.values)
				 || filterFormat == 'language_english_name_with_id'
				 || filterFormat == 'language_english_name'
				 || filterFormat == 'language_local_name_with_id'
				 || filterFormat == 'language_local_name'
				) {
					var values,
						empty_value = undefined;
					
					if (filterFormat == 'enum') {
						//Get the list of values for this column, adding on an empty value if one is not present
						values = column.values;
						if (!values[''] && !values[0]) {
							values = {};
							empty_value = phrase.selectListSelect;
							
							foreach (column.values as var v) {
								values[v] = column.values[v];
							}
						}
					
					} else {
						values = {};
						values[''] = phrase.selectListSelect;
						
						foreach (zenarioA.lang as var v) {
							if (zenarioA.lang[v].enabled) {
								values[v] = zenarioA.lang[v].name;
								
								if (filterFormat == 'language_english_name_with_id'
								 || filterFormat == 'language_local_name_with_id') {
									values[v] += ' (' + v + ')';
								}
							}
						}
					}
					
					zenarioVO.tuix.tabs.cp.fields['v' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field enum',
						label: invertLink + (zenarioO.getFilterValue('not', c)? phrase.isnt : phrase.is) + '</a>',
						type: 'select',
						onchange: 'zenarioO.changeFilters();',
						empty_value: empty_value,
						values: values,
						value: value_,
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
				
				} else if (engToBoolean(column.searchable)) {
					
					var labelPhrase;
					if (zenarioO.getFilterValue('not', c)) {
						labelPhrase = column.format == 'id'? phrase.isnt : phrase.notLike;
					} else {
						labelPhrase = column.format == 'id'? phrase.is : phrase.like;
					}
					
					zenarioVO.tuix.tabs.cp.fields['v' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field',
						label: invertLink + labelPhrase + '</a>',
						type: 'text',
						onkeyup: 'zenarioVO.changeFiltersAfterDelay();',
						value: value_,
						hidden: hidden,
						_was_hidden_before: hiddenPreviously
					};
				}
				
				if (zenarioO.getColumnFilterType(c)) {
					zenarioVO.tuix.tabs.cp.fields['togglefilter_' + c] = {
						ord: 100 * colNo + 2,
						same_row: true,
						pre_field_html: '<div class="organizer_togglefilter">',
						type: 'toggle',
						onclick: "zenarioO.toggleFilter(this, '" + htmlspecialchars(c) + "');",
						'class': hidden? 'togglefilter_closed' : 'togglefilter_open',
						value: hidden? ' ' : 'x',
						title: hidden? phrase.filterByCol : phrase.filterByColStop,
						post_field_html: '</div>'
					};
				}
			}
			
			if (zenarioO.showCSVInViewOptions) {
				zenarioVO.tuix.tabs.cp.fields['up__' + c] = {
					ord: 100 * colNo + 1,
					same_row: true,
					pre_field_html: '<div class="organizer_mov_col">',
					type: 'toggle',
					value: ' ',
					'class': 'movcol_up',
					style: 'visibility: hidden;',
					title: phrase.moveColForward
				};
				
				zenarioVO.tuix.tabs.cp.fields['down__' + c] = {
					ord: 100 * colNo + 2,
					same_row: true,
					type: 'toggle',
					value: ' ',
					'class': 'movcol_down',
					style: 'visibility: hidden;',
					title: phrase.moveColBack,
					post_field_html: '</div>'
				};
				
				if (lastCol !== false) {
					zenarioVO.tuix.tabs.cp.fields['up__' + c].style = '';
					zenarioVO.tuix.tabs.cp.fields['up__' + c].onclick = 'zenarioO.switchColumnOrder(' + colNo + ', ' + lastCol + ', true)';
					
					zenarioVO.tuix.tabs.cp.fields['down__' + lastColName].style = '';
					zenarioVO.tuix.tabs.cp.fields['down__' + lastColName].onclick = 'zenarioO.switchColumnOrder(' + colNo + ', ' + lastCol + ', true)';
				}
			}
			lastCol = colNo;
			lastColName = c;
		}
	}
	
	
	var p,
		pref,
		pageSize,
		showResetButton = false;
	
	if (zenarioO.branches[zenarioO.branches.length-1]
	 && zenarioO.branches[zenarioO.branches.length-1].filters
	 && zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path]) {
		foreach (zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path] as var p) {
			if (zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][p].shown) {
				showResetButton = true;
				break;
			}
		}
	}
	
	foreach (prefs as p => pref) {
		if (p && pref) {
			showResetButton = true;
			break;
		}
	}
	
	if (showResetButton) {
		zenarioVO.tuix.tabs.cp.fields.reset = {
			ord: 100 * colNo + 12,
			full_width: true,
			type: 'toggle',
			onclick: "zenarioO.resetPrefs();",
			value: phrase.reset
		};
	}
	
	//html += zenarioVO.draw2();
	
	zenarioVO.sortTabs();
	var cb = new zenario.callback,
		html = zenarioVO.drawFields(cb);
	
	get('zenario_fbAdminViewModeOptions').innerHTML = html;
	
	zenario.addJQueryElements('#zenario_fbAdminViewModeOptions ', true);
	cb.call();
	
	zenarioO.size(true);
};

zenarioO.updateDateFilters = function(c) {
	
	//var getDateFromField = function(id) {
	//		return $.datepicker.formatDate($.datepicker.ATOM, $(get(id)).datepicker('getDate'));
	//	},
	//	dateAfter = getDateFromField('date_after_col_' + c),
	//	dateBefore = getDateFromField('date_before_col_' + c),
	
	var dateAfter = zenarioVO.readField('date_after_col_' + c),
		dateBefore = zenarioVO.readField('date_before_col_' + c),
		domValue = get('v' + c);
	
	if (dateAfter || dateBefore) {
		domValue.value = dateAfter + ',' + dateBefore;
	} else {
		domValue.value = '';
	}
	
	zenarioO.changeFilters();
};

zenarioO.canSortColumn = function(c) {
	if (zenarioO.stop
	 || engToBoolean(zenarioO.tuix.columns[c].disallow_sorting)
	 || (zenarioO.server_side && !zenarioO.tuix.columns[c].db_column)) {
		return false;
	}
	return true;
};

zenarioO.canFilterColumn = function(c) {
	//Only allow filtering for columns in the database
	if (!zenarioO.tuix.columns[c].db_column
	 || engToBoolean(zenarioO.tuix.columns[c].server_side_only)
	 || engToBoolean(zenarioO.tuix.columns[c].disallow_filtering)) {
		return false;
	}
	
	var filterFormat = zenarioO.tuix.columns[c].filter_format || zenarioO.tuix.columns[c].format;
	
	//Catch the case where an enum column is missing its values - don't allow filtering here
	if (filterFormat == 'enum' && !zenarioO.tuix.columns[c].values) {
		return false;
	}
	
	switch (filterFormat) {
		//Date and enum type fields are implemented as a drop-down select list or toggle.
		//They should be filterable if an index has been created on that field.
		case 'date':
		case 'datetime':
		case 'datetime_with_seconds':
		case 'language_english_name_with_id':
		case 'language_english_name':
		case 'language_local_name_with_id':
		case 'language_local_name':
			return !engToBoolean(zenarioO.tuix.columns[c].disallow_sorting);
		
		//Text type fields are implemented as a search box.
		//They should be filterable if they are flagged as searchable.
		default:
			return engToBoolean(zenarioO.tuix.columns[c].searchable);
	}
};

zenarioO.showHideColumn = function(show, c) {
	//Ignore requests to hide columns that are forced on
	if (engToBoolean(zenarioO.tuix.columns[c].always_show)) {
		show = true;
	
	} else {
		//Ensure someone can never hide the last column!
		var count = 0;
		foreach (zenarioO.tuix.columns as var i) {
			if (zenarioO.shownColumns[i]) {
				++count;
			}
		}
	
		if (count < 2 && !show) {
			show = true;
		}
	}
	
	//Otherwise change this column's visibility
	if (show) {
		zenarioO.shownColumns[c] = true;
	} else {
		zenarioO.shownColumns[c] = false;
	}
	
	zenarioO.checkPrefs();
	zenarioO.prefs[zenarioO.path].shownColumns = zenarioO.shownColumns;
	zenarioO.savePrefs();
	
	zenarioO.setPanel();
};

zenarioO.resizeColumn = function(c, size) {
	if (zenarioO.shownColumns[c]) {
		size = Math.max(size, zenarioO.columnWidths.xxsmall);
		
		zenarioO.checkPrefs();
		if (!zenarioO.prefs[zenarioO.path].colSizes) {
			zenarioO.prefs[zenarioO.path].colSizes = {};
		}
		zenarioO.prefs[zenarioO.path].colSizes[c] = size;
		zenarioO.savePrefs();
	}
	
	zenarioO.setPanel();
};

zenarioO.showHideColumnInCSV = function(el, c) {
	zenarioO.checkPrefs();
	
	if (!zenarioO.prefs[zenarioO.path].shownColumnsInCSV) {
		zenarioO.prefs[zenarioO.path].shownColumnsInCSV = {};
	}
	
	if (el.checked) {
		zenarioO.prefs[zenarioO.path].shownColumnsInCSV[c] = true;
	} else {
		zenarioO.prefs[zenarioO.path].shownColumnsInCSV[c] = false;
	}
	
	zenarioO.savePrefs();
};

zenarioO.switchColumnOrder = function(a, b, viewOptions) {
	if (typeof a == 'object') {
		var n,
			col,
			columnsToMove = {};
			moveableColumns = {},
			pi = -1,
			positions = [];
		
		foreach (a as n) {
			col = a[n];
			
			columnsToMove[col] = true;
		}
		
		foreach (zenarioO.sortedColumns as n) {
			col = zenarioO.sortedColumns[n];
			
			if (columnsToMove[col]) {
				moveableColumns[col] = true;
				positions[++pi] = n;
				
				zenarioO.sortedColumns[n] = undefined;
			}
		}
		
		pi = -1;
		foreach (a as n) {
			col = a[n];
			
			if (columnsToMove[col] && moveableColumns[col]) {
				zenarioO.sortedColumns[positions.shift()] = col;
			}	
		}
		
		for (n = zenarioO.sortedColumns.length - 1; n >= 0; --n) {
			if (zenarioO.sortedColumns[n] === undefined) {
				zenarioO.sortedColumns.splice(n, 1);
			}
		}
		
	} else {
		var tmp = zenarioO.sortedColumns[a];
		zenarioO.sortedColumns[a] = zenarioO.sortedColumns[b];
		zenarioO.sortedColumns[b] = tmp;
	}
	
	zenarioO.checkPrefs();
	zenarioO.prefs[zenarioO.path].sortedColumns = zenarioO.sortedColumns;
	
	zenarioO.setPanel();
	
	if (viewOptions) {
		zenarioO.setViewOptions();
	}
	
	zenarioO.savePrefs();
};

zenarioO.resetPrefs = function() {
	
	zenarioO.checkPrefs();
	if (zenarioO.prefs[zenarioO.path]) {
		zenarioO.prefs[zenarioO.path] = {};
		zenarioO.savePrefs();
	}
	
	if (zenarioO.branches[zenarioO.branches.length-1]
	 && zenarioO.branches[zenarioO.branches.length-1].filters) {
		zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path] = {};
	}
	
	zenarioA.closeBox('AdminViewModeOptions');
	
	zenarioO.refreshAndShowPage();
};



zenarioO.checkIfColumnPickerChangesAreAllowed = function(c) {
	//Don't allow the sort order to be changed when reordering
	if (zenarioO.stop) {
		return false;
	}
	
	return true;
};

zenarioO.changeSortOrder = function(c) {
	if (!zenarioO.canSortColumn(c)
	 || !zenarioO.checkIfColumnPickerChangesAreAllowed(c)) {
		return;
	}
	
	if (zenarioO.sortBy == c) {
		zenarioO.sortDesc = !zenarioO.sortDesc;
	} else {
		zenarioO.sortBy = c;
		zenarioO.sortDesc = false;
	}
	
	zenarioO.checkPrefs();
	zenarioO.prefs[zenarioO.path].sortBy = zenarioO.sortBy;
	zenarioO.prefs[zenarioO.path].sortDesc = zenarioO.sortDesc;
	
	if (zenarioO.pi) {
		zenarioO.pi.cmsSetsSortColumn(zenarioO.sortBy, zenarioO.sortDesc);
	}
	zenarioO.runSearch();
	
	//This line is not needed now we don't use the view options for sorting columns
	//zenarioO.setViewOptions();
	
	zenarioO.savePrefs(true);
};

zenarioO.toggleFilter = function(el, c) {
	
	var shown,
		filterType = zenarioO.getColumnFilterType(c);
	
	if (!filterType
	 || !zenarioO.checkIfColumnPickerChangesAreAllowed(c)) {
		return;
	}
	
	//Toogle whether this filter is hidden or shown
	shown = !zenarioO.getFilterValue('s', c);
	zenarioO.setFilterValue('s', c, shown);
	
	//Unset values of hidden filters
	var refreshNeeded = false;
	if (!shown) {
		if (zenarioO.getFilterValue('v', c)) {
			zenarioO.setFilterValue('v', c, '')
			refreshNeeded = true;
		}
		zenarioO.setFilterValue('not', c, false);
	}
	
	//yes_or_no type filters should turn on by default if activated
	if (filterType == 'yes_or_no') {
		if (shown) {
			refreshNeeded = true;
			zenarioO.setFilterValue('v', c, 1);
		}
	}
	
	
	zenarioO.setViewOptions(c);
	
	//Animate in/out the field that was just shown/hidden
	zenarioVO.hideShowFields(function() {
		zenarioO.size(true);
		//Focus a text field straight away if we can
		if (get('v' + c) && $(get('v' + c)).is(':visible')) {
			get('v' + c).focus();
		}
	});
	
	
	//If a filter with a value set was hidden, we will need to refresh to update the panel with the changes
	if (refreshNeeded) {
		zenarioO.refreshAndShowPage();
	}
};

zenarioO.invertFilter = function(el, c, format) {
	if (!zenarioO.checkIfColumnPickerChangesAreAllowed(c)) {
		return;
	}
	
	//Toogle whether this filter is inverted or not
	var not = !zenarioO.getFilterValue('not', c);
	zenarioO.setFilterValue('not', c, not);
	
	//Redraw the form to change the button
	zenarioO.setViewOptions(c);
	
	//If a filter with a value set was inverted, we will need to refresh to update the panel with the changes
	if (zenarioO.filterSetOnColumn(c)) {
		zenarioO.refreshAndShowPage();
	}
};

zenarioO.refreshIfFilterSet = function(c) {
	if (zenarioO.getFilterValue('v', c)) {
		zenarioO.refreshAndShowPage();
	}
};

zenarioO.filterSetOnColumn = function(c, filters) {
	return !!zenarioO.getFilterValue('v', c, filters);
};

zenarioO.getFilterValue = function(filter, c, filters) {
	if (filters === undefined) {
		filters = zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path];
	}

	if (filters
	 && filters[c]
	 && filters[c][filter]) {
		return filters[c][filter];
	} else {
		return '';
	}
};

zenarioO.setFilterValue = function(filter, c, value) {
	
	var filters = zenarioO.branches[zenarioO.branches.length-1].filters;
	
	if (!filters[zenarioO.path]) {
		filters[zenarioO.path] = {};
	}
	if (!filters[zenarioO.path][c]) {
		filters[zenarioO.path][c] = {};
	}
	
	//Store the value.
	//A couple of little hacks to save space here:
		//There's no difference between {not: false} and {not: undefined} so we can delete "not" if it is false
		//Also "not" and "show" is used a as a boolean, so we can convert {s: true} to {s: 1} and {s: false} to {s: 0}
	if (value === undefined
	 || (!value && c == 'not')) {
		delete filters[zenarioO.path][c][filter];
	
	} else if (c == 's' || c == 'not') {
		filters[zenarioO.path][c][filter] = engToBoolean(value);
	
	} else {
		filters[zenarioO.path][c][filter] = value;
	}
	
	if (_.isEmpty(filters[zenarioO.path][c])) {
		delete filters[zenarioO.path][c];
	}
	if (_.isEmpty(filters[zenarioO.path])) {
		delete filters[zenarioO.path];
	}
};

zenarioO.clearFilter = function(c) {
	zenarioO.setFilterValue('v', c);
	zenarioO.setFilterValue('not', c);
};


zenarioVO.changeFiltersAfterDelay = function() {
	zenario.actAfterDelayIfNotSuperseded('changeFilters', zenarioO.changeFilters, 700);
};

zenarioO.changeFilters = function() {
	
	//Don't allow the filters to be touched when reordering
	if (zenarioO.stop) {
		zenarioO.setViewOptions();
		return;
	}
	
	
	var value;
	foreach (zenarioO.tuix.columns as var c) {
		if (zenarioO.isShowableColumn(c)) {
			if (get('v' + c) || get('v' + c + '___yes')) {
				if (value = zenarioVO.readField('v' + c)) {
					zenarioO.setFilterValue('v', c, value);
				} else {
					zenarioO.setFilterValue('v', c, '');
				}
			}
			if (get('remove_filter_' + c)) {
				get('remove_filter_' + c).className =
					zenarioO.filterSetOnColumn(c)? 'organizer_remove_filter organizer_remove_filter_active' : 'organizer_remove_filter organizer_remove_filter_inactive'
			}
		}
	}
	
	zenarioO.refreshAndShowPage();
};

zenarioO.changePageSize = function(newPageSize) {
	
	//Don't allow the filters to be touched when reordering
	if (zenarioO.stop) {
		//zenarioO.setViewOptions();
		return;
	}
	
	zenarioO.checkPrefs();
	if (1*newPageSize) {
		zenarioO.prefs[zenarioO.path].pageSize = 1*newPageSize;
	} else {
		delete zenarioO.prefs[zenarioO.path].pageSize;
	}
	
	//zenarioO.setViewOptions();
	zenarioO.refreshAndShowPage();
	zenarioO.savePrefs(true);
};


zenarioO.hideViewOptions = function(e) {
	zenarioA.closeBox('AdminViewModeOptions');
	return false;
};







zenarioO.isShowableColumn = function(c, shown) {
	if (shown) {
		return zenarioO.shownColumns[c] && zenarioO.isShowableColumn(c);
	} else {
		return zenarioO.tuix.columns[c] && !zenarioA.hidden(zenarioO.tuix.columns[c]) && zenarioO.tuix.columns[c].title && !engToBoolean(zenarioO.tuix.columns[c].server_side_only);
	}
};


//  Sorting Functions  //

zenarioO.getSortedIdsOfTUIXElements = function(toSort, column, desc) {
	return zenarioA.getSortedIdsOfTUIXElements(zenarioO.tuix, toSort, column, desc);
};

//Given two elements from the above function, say which order they should be in
zenarioO.sortArray = function(a, b) {
	if (a[1] === b[1]) {
		//If their values are the same type and identical, say that they're identical
		return 0;
	
	} else if (a[2]? b[2] : !b[2]) {
		//If they're the same type, use a < to work out which is smallest
		return a[1] < b[1]? -1 : 1;
	
	} else {
		//Otherwise order by numeric data first, then strings
		return a[2]? -1 : 1;
	}
};

zenarioO.sortArrayDesc = function(a, b) {
	return zenarioO.sortArray(b, a);
};



zenarioO.dateDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
zenarioO.dateMonths = ['January','Feburary','March','April','May','June','July','August','September','October','November','December'];





zenarioO.checkCondition = function(condition) {
	var id,
		selectedItems = zenarioO.pi.returnSelectedItems();
	
    foreach (selectedItems as var id) {
    	if (!zenarioO.tuix.items[id]) {
    		return false
    	
    	} else if (!condition(id, zenarioO.tuix.items[id])) {
            return false;
        }
    }
    return true;
};

zenarioO.columnEqual = function(column, value) {
	return zenarioO.checkCondition(function(id) {
		return zenarioO.columnRawValue(id, column) == value;
	});
};

zenarioO.columnNotEqual = function(column, value) {
	return zenarioO.checkCondition(function(id) {
		return zenarioO.columnRawValue(id, column) != value;
	});
};

zenarioO.columnRawValue = function(i, c) {
	
	if (!zenarioO.tuix
	 || !zenarioO.tuix.columns
	 || zenarioO.tuix.columns[c] === undefined
	 || zenarioO.tuix.items[i] === undefined) {
		return '';
	}
	
	return zenarioO.tuix.items[i][c];
};

zenarioO.columnValue = function(i, c, dontHTMLEscape) {
	
	if (!zenarioO.tuix || !zenarioO.tuix.columns || zenarioO.tuix.columns[c] === undefined) {
		return '';
	}
	
	var value = zenarioO.tuix.items[i][c];
	if (value === undefined || value === false || value === null) {
		value = '';
	} else {
		value = '' + value;
	}
	
	
	//Is this item an item link..?
	var item_link = zenarioO.tuix.columns[c].item_link,
		isSKLink = true,
		isURL = false,
		itemName;
	
	if (item_link) {
		var item = false;
		
		switch (item_link) {
			case 'content_item':
			case 'content_item_or_url':
				var lang = zenarioO.itemLanguage(i),
					parent = zenarioO.itemParent(i);
		
				if (value
				 && zenarioO.contentItems[lang]
				 && zenarioO.contentItems[lang][parent]
				 && (item = zenarioO.contentItems[lang][parent].items)
				 && (item = item[value])) {
			
				} else if (item_link == 'content_item_or_url'
						&& value
						&& value.substr(0, 1) != '_'
						&& value.substr(1, 2) != '_'
				) {
					item = {name: value, frontend_link: value};
					isSKLink = false;
		
				} else {
					return '';
				}
				
				break;
			
			case 'menu_item':
				var lang = zenarioO.itemLanguage(i);
				if (value && zenarioO.menuItems[lang] && (item = zenarioO.menuItems[lang].items) && (item = item[value])) {}
				
				break;
			
			default:
				if (value && zenarioO.otherItemLinks[item_link] && (item = zenarioO.otherItemLinks[item_link].items) && (item = item[value])) {}
			
		}
		
		if (item) {
			if (dontHTMLEscape) {
				return item.name;
			} else {
				
				var href = '';
				
				if (zenarioO.tuix.items[i].cell_css_classes
				 && zenarioO.tuix.items[i].cell_css_classes[c]
				 && ('' + zenarioO.tuix.items[i].cell_css_classes[c]).indexOf('ghost') != -1) {
					//Don't allow ghosted Item Links to be clickable
					href = ' style="cursor: default;"';
					
					if (item_link == 'menu_item') {
						href += ' title="' + htmlspecialchars(item.name) + '|"';
					
					} else if (item_link == 'content_item' || item_link == 'content_item_or_url') {
						href += ' title="' + htmlspecialchars(item.name) + '|"';
					}
				
				} else {
					if (isSKLink && !window.zenarioONotFull) {
						
						var extraParams = '',
							navPath,
							tagPath = zenarioO.shallowLinks[item_link] || item_link;
						
						if (item.navigation_path) {
							navPath = item.navigation_path;
						
						} else if (zenarioO.shallowLinks[item_link]) {
							navPath = zenarioO.shallowLinks[item_link] + '//' + value;
						
						} else {
							navPath = item_link + '//' + value;
						}
						
						if (zenarioO.shallowLinks[item_link]) {
							extraParams = ", name: 'following_item_link', languageId: '" + htmlspecialchars(zenarioO.itemLanguage(i)) + "'";
						}
						
						href =
							' href="organizer.php#' + htmlspecialchars(navPath) + '" onclick="' +
								"zenarioO.deselectAllItems();" +
								"var selectedItems = {};" +
								"selectedItems['" + htmlspecialchars(i) + "'] = true;" +
								"zenarioO.pi.cmsSetsSelectedItems(selectedItems);" +
								"zenarioO.setHash();" +
								"zenarioO.go('" + htmlspecialchars(tagPath) + "', true, {id: '" + htmlspecialchars(value) + "'" + extraParams + "}, undefined, undefined, undefined, undefined, '" + htmlspecialchars(value) + "');" +
								"return zenario.stop(event);" +
							'"';
					
					} else if (item.navigation_path) {
						href = ' href="' + URLBasePath + 'zenario/admin/organizer.php#/' + htmlspecialchars(item.navigation_path) + '" target="_blank"';
					
					} else {
						isSKLink = false;
						if (item.frontend_link) {
							isURL = true;
							href = ' href="' + htmlspecialchars(zenario.addBasePath(item.frontend_link)) + '" target="_blank"';
						}
					}
					
					if (isSKLink) {
						if (item_link == 'menu_item') {
							href += ' title="' + htmlspecialchars(item.name) + '|' + phrase.clkToViewLinkedMenuNode + '"';
						
						} else if (item_link == 'content_item' || item_link == 'content_item_or_url') {
							href += ' title="' + htmlspecialchars(item.name) + '|' + phrase.clkToViewLinkedCItem + '"';
						}
					
					} else if (isURL) {
						href += ' title="' + htmlspecialchars(item.name) + '|' + phrase.clkToViewLinkInNewWindow + '"';
					}
				}
				
				
				switch (item_link) {
					case 'content_item':
					case 'content_item_or_url':
						itemName = item.name;
						break;
			
					case 'menu_item':
						var longName = htmlspecialchars(item.name);
						var shortName = longName.replace(/.*?\-\&gt\; /g, '-&gt; ');
					
						if (shortName == longName) {
							shortName = longName.replace(/.*?\: /g, '');
						}
						
						itemName = shortName;
						break;
			
					default:
						itemName = zenarioA.formatOrganizerItemName(zenarioO.otherItemLinks[item_link], value);
				}
				
				return zenarioA.microTemplate('zenario_organizer_item_link', {
					item: item,
					item_link: item_link,
					itemName: itemName,
					href: href,
					panel: zenarioO.otherItemLinks[item_link],
					value: value
				});
			}
		}
	}
	
	
	//Otherwise check to see if this is a formated column
	if (zenarioO.tuix.columns[c].format || zenarioO.tuix.columns[c].empty_value) {
		value = zenarioA.formatSKItemField(value, zenarioO.tuix.columns[c]);
	}
	
	
	var applyLengthLimit =
		zenarioO.tuix.columns[c].length_limit
	 && value.length > zenarioO.tuix.columns[c].length_limit;
	
	if (dontHTMLEscape) {
		if (applyLengthLimit) {
			return value.substr(0, zenarioO.tuix.columns[c].length_limit) + '...';
		} else {
			return value;
		}
	
	} else {
		if (applyLengthLimit) {
			return '<span class="tooltip" title="' + htmlspecialchars(value) + '">' +
						htmlspecialchars(value.substr(0, zenarioO.tuix.columns[c].length_limit)) +
					'...</span>';
		
		} else {
			return htmlspecialchars(value);
		}
	}
};

zenarioO.rowCssClass = function(i) {
	if (zenarioO.tuix.items[i] && zenarioO.tuix.items[i].row_css_class) {
		return ' ' + zenarioO.tuix.items[i].row_css_class;
	} else {
		return '';
	}
};

zenarioO.columnCssClass = function(c, i) {
	var html = '';
	
	if (zenarioO.tuix.columns[c] && zenarioO.tuix.columns[c]['css_class']) {
		html += ' ' + zenarioO.tuix.columns[c]['css_class'];
	}
	
	if (i != undefined
	 && zenarioO.tuix.items[i]
	 && zenarioO.tuix.items[i].cell_css_classes
	 && zenarioO.tuix.items[i].cell_css_classes[c]) {
		html += ' ' + zenarioO.tuix.items[i].cell_css_classes[c];
	}
	
	return html;
};




//	Display Functions  //

//Warning: the zenarioO.setBackButton() and zenarioO.getBackButtonTitle() functions both
//rely on the zenarioO.setNavigation() function being called first!
zenarioO.setBackButton = function() {
	var i,
		html = '',
		titles = zenarioO.getBackButtonTitle(-1, true),
		data = {
			buttons: []
		};
	
	if (!titles || titles.length == 0) {
		get('organizer_branding_title').style.display = 'block';
		get('organizer_backButton').style.display = 'none';
	
	} else {
		get('organizer_branding_title').style.display = 'none';
		get('organizer_backButton').style.display = 'block';
		
		foreach (titles as i) {
			data.buttons[i] = {
				orderAsc: i + 1,
				orderDesc: titles.length - i,
				title: titles[i]
			};
		}
		
		html = zenarioA.microTemplate('zenario_organizer_back_buttons', data);
	}
	
	get('organizer_backButton').innerHTML = html;
	zenarioA.tooltips('#organizer_backButton *[title]');
	zenarioA.setTooltipIfTooLarge('.organizer_lastBackButton a', undefined, zenarioA.tooltipLengthThresholds.organizerBackButton);
};

zenarioO.getBackButtonTitle = function(times, getArray) {
	var branchLevel = zenarioO.branches.length - 1;
	return zenarioO.getFromLastPanel(branchLevel, zenarioO.path, 'title', false, times, getArray);
};

zenarioO.getSelectedItemFromLastPanel = function(path) {
	var id,
		selectedItems = false;
	
	if ((panelInstance = zenarioO.getFromLastPanel(zenarioO.branches.length-1, zenarioO.path, 'panel_instances', true))
	 && (panelInstance = panelInstance[path])) {
		selectedItems = panelInstance.returnSelectedItems();
	}
	
	if (selectedItems) {
		foreach (selectedItems as id) {
			return id;
		}
	}
	
	return false;
};

zenarioO.getFromLastPanel = function(branchLevel, path, thing, branchBelow, times, getArray, stats, secondCall) {
	
	if (!zenarioO.tuix || !zenarioO.path) {
		return false;
	}
	
	var output,
		arrayOut = false,
		hash,
		backLink,
		goDownBranch = false,
		goUpPath = false;
	
	if (times === undefined) {
		times = 1;
	}
	if (stats === undefined) {
		stats = {};
	}
	if (stats.path === undefined) {
		stats.path = path;
	}
	if (stats.pops === undefined) {
		stats.pops = 0;
	}
	
		//Don't go further back than the top-level panel
	if (branchLevel === 0 && path.indexOf('/') == -1) {
		return false;
	}
	
	if (thing != 'no_return') {
			//Don't allow the back button if the last panel used the "no return" flag
		if (engToBoolean(zenarioO.getFromLastPanel(branchLevel, path, 'no_return'))
			
			//Don't allow the back button if it has been disabled using <back_link></back_link>
		 || (branchLevel === 0 && !zenarioO.tuix.back_link)
		 
			//If the current top level has a refiner in it, treat the end-point of the refiner
			//(i.e. the second branch-level) as the start and don't allow anything to reference
			//the first branch level (which you can never actually navigate to).
		 || (branchLevel < 2 && zenarioO.currentTopLevelPathHasRefiner)) {
			
			return false;
		}
	}

	//Is this the start of the branch?
	if (branchLevel > 0 && path == zenarioO.branches[branchLevel].to) {
		//If so, look for the previous panel
		
		//Don't allow the admin to go above the min-path in select mode
		if (window.zenarioONotFull && window.zenarioOMinPath && path == window.zenarioOMinPath && (window.zenarioODisallowRefinersLoopingOnMinPath || zenarioO.branches[branchLevel].from != path)) {
			return false;
		
		//If the previous panel was a bypass, call zenarioO.getFromLastPanel() again to get the title of the panel before that.
		} else if (zenarioO.branches[branchLevel-1].bypasses[zenarioO.branches[branchLevel].from]
			
			//Note that bypasses are only used for item links, so we need to check if actually is an item link just in case we disallow the back button when it should in fact be allowed
		 && zenarioO.knownBranches[zenarioO.branches[branchLevel].from]
		 && zenarioO.knownBranches[zenarioO.branches[branchLevel].from][zenarioO.branches[branchLevel].to]
		 && zenarioO.knownBranches[zenarioO.branches[branchLevel].from][zenarioO.branches[branchLevel].to][zenarioO.branches[branchLevel].refiners[zenarioO.branches[branchLevel].to]? zenarioO.branches[branchLevel].refiners[zenarioO.branches[branchLevel].to].name : 1]
		 && zenarioO.knownBranches[zenarioO.branches[branchLevel].from][zenarioO.branches[branchLevel].to][zenarioO.branches[branchLevel].refiners[zenarioO.branches[branchLevel].to]? zenarioO.branches[branchLevel].refiners[zenarioO.branches[branchLevel].to].name : 1].substr(-4) == 'item'
		) {
			return zenarioO.getFromLastPanel(branchLevel-1, zenarioO.branches[branchLevel].from, thing, branchBelow, times, getArray, stats, true);
		
		//Set the title of the back button to the last panel in the previous branch.
		//Return eiher the current branch or the branch below if branchBelow is set.
		} else {
			goDownBranch = true;
			
			if (branchBelow) {
				output = zenarioO.branches[branchLevel-1][thing];
			} else {
				output = zenarioO.branches[branchLevel][thing];
			}
		}
	
	} else if (zenarioO.branches[branchLevel][thing]) {
		goDownBranch = true;
		output = zenarioO.branches[branchLevel][thing];
	
	} else {
		//Otherwise check to see if there is a panel naturally above the current panel and use the title from there
		if (!secondCall && path == zenarioO.path) {
			backLink = zenarioO.tuix.back_link;
		} else {
			backLink = zenarioO.followPathOnMap(path, 'back_link');
		}
		
		
		if (!backLink) {
			return false;
		
		//Don't allow the admin to go above the min-path in select mode
		} else if (window.zenarioONotFull && window.zenarioOMinPath && path == window.zenarioOMinPath && (window.zenarioODisallowRefinersLoopingOnMinPath || backLink != path)) {
			return false;
		
		//Otherwise just attempt to read the title off from the map
		} else {
			goUpPath = true;
			output = zenarioO.followPathOnMap(backLink, thing);
		}
	}
	
	if (goDownBranch) {
		stats.path = zenarioO.branches[branchLevel].from;
		++stats.pops;
	
	} else if (goUpPath && backLink) {
		stats.path = backLink;
	}
	
	if (--times != 0) {
		if (goDownBranch) {
			arrayOut = zenarioO.getFromLastPanel(branchLevel-1, zenarioO.branches[branchLevel].from, thing, branchBelow, times, getArray, stats, true);
		
		} else if (goUpPath && backLink) {
			arrayOut = zenarioO.getFromLastPanel(branchLevel, backLink, thing, branchBelow, times, getArray, stats, true);
		
		} else {
			//should never happen
			return false;
		}
	}
	
	if (getArray) {
		if (!arrayOut) {
			arrayOut = [];
		}
		arrayOut[arrayOut.length] = output;
		
		return arrayOut;
	
	} else if (arrayOut !== false) {
		return arrayOut;
	
	} else {
		return output;
	}
};



//Warning: the zenarioO.setBackButton() and zenarioO.getBackButtonTitle() functions both
//rely on the zenarioO.setNavigation() function being called first!
zenarioO.setNavigation = function() {
	var i, j,
		itemNo, jtemNo,
		ti = -1,
		selected1st,
		selected2nd,
		length,
		longestMatch = -1,
		commonPrefix,
		sortedSecondLevelItems,
		data = {
			items: [],
			selected1st: 0
		},
		
		//Work out which top level item was selected, either by looking at the first branch, or if there are no
		//branches, the current path.
		path = zenarioO.path,
		navPath = zenarioO.getHash(true),
		thisNavPath;
	
	zenarioO.currentTopLevelPathHasRefiner = false;
	
	if (zenarioO.branches.length > 1) {
		path = zenarioO.branches[1].from;
	}
	
	
	//Loop through all of the first and second level navs
	foreach (zenarioO.map as i) {
		if (i != 'top_right_buttons'
		 && typeof zenarioO.map[i] == 'object'
		 && zenarioO.map[i].nav) {
			foreach (zenarioO.map[i].nav as j) {
				if (typeof zenarioO.map[i].nav[j] == 'object') {
					
					//Work out which first/second level nav should be selected by comparing its path against the navigation path
					thisNavPath = i + '/nav/' + j + '/panel';
					
					if (zenarioO.map[i].nav[j].link
					 && zenarioO.map[i].nav[j].link.path) {
						thisNavPath = zenarioO.map[i].nav[j].link.path;
					}
					
					length = (thisNavPath + '').length;
					
					if (longestMatch < length
					 && (commonPrefix = (navPath + '/').substr(0, length))
					 && (commonPrefix == thisNavPath
					  || commonPrefix == thisNavPath + '/')) {
						longestMatch = length;
						selected1st = i;
						selected2nd = j;
					}
				}
			}
		}
	}
	
	
	
	//Loop through each top level nav
	foreach (zenarioO.sortedTopLevelItems as itemNo) {
		i = zenarioO.sortedTopLevelItems[itemNo];
		
		if (i == 'top_right_buttons'
		 || zenarioA.hidden(zenarioO.map[i])) {
			continue;
		}
		
		var first2nd = true,
			si = -1,
			prop,
			nav;
		
		//Set data to pass to the microtemplate
		data.items[++ti] = {
			id: i,
			items: [],
			selected: i === selected1st,
			css_class: zenarioO.map[i].css_class,
			label: ifNull(zenarioO.map[i].label, zenarioO.map[i].name),
			tooltip: zenarioO.map[i].tooltip,
			youtube_video_id: zenarioO.map[i].youtube_video_id,
			youtube_thumbnail_title: zenarioO.map[i].youtube_thumbnail_title
		};
		zenarioO.setDataAttributes(zenarioO.map[i], data.items[ti]);
		
		if (i === selected1st) {
			data.selected1st = ti;
		}
		
		//Sort the second level nav under this top level
		if ((nav = zenarioO.map[i].nav)
		 && (_.isObject(nav))) {
			sortedSecondLevelItems = zenarioO.getSortedIdsOfTUIXElements(zenarioO.map[i].nav);
		
			//Loop through each second level nav
			foreach (sortedSecondLevelItems as jtemNo => j) {
			
				if (zenarioO.map[i].nav[j].link
				 && zenarioO.map[i].nav[j].link.path) {
					path = zenarioO.map[i].nav[j].link.path;
				} else {
					path = i + '/nav/' + j + '/panel';
				}
			
				if (i === selected1st && j === selected2nd) {
					zenarioO.currentTopLevelPath = path;
				
					if (path.match(/\/\//)) {
						zenarioO.currentTopLevelPathHasRefiner = true;
					}
				}
			
				if (zenarioA.hidden(zenarioO.map[i].nav[j])) {
					continue;
				}
			
				data.items[ti].items[++si] = {
					id: j,
					selected: i === selected1st && j === selected2nd,
					href: '#' + path,
					onclick: "zenarioO.topLevelClick('" + jsEscape(i) + "', '" + jsEscape(j) + "', " + engToBoolean(si == 0) + "); return false;",
					css_class: zenarioO.map[i].nav[j].css_class,
					label: ifNull(zenarioO.map[i].nav[j].label, zenarioO.map[i].nav[j].name),
					tooltip: zenarioO.map[i].nav[j].tooltip
				};
			
				//The user should be taken to the first second level item if they click on the top level item
				if (first2nd) {
					first2nd = false;
					data.items[ti].href = data.items[ti].items[si].href;
					data.items[ti].onclick = data.items[ti].items[si].onclick;
				}
			}
		}
	}	
	
	
	get('organizer_leftColumn').innerHTML = zenarioA.microTemplate('zenario_organizer_nav', data);
	
	//Set tooltips for top-level nav
	zenarioA.tooltips('#organizer_leftColumn #organizer_topLevelNav a[title]', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}, show: false, hide: false});
	
	//Set tooltips for second-level nav
	zenarioA.tooltips('#organizer_leftColumn .organizer_sectionTitleTextAnd2ndLevelNav a[title]', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
	
	
	var $topLevelNav = $('#organizer_topLevelNav');
	$('#organizer_topLevelNavScrollUp').mousehold(function() {
		zenarioO.scrollTopLevelNav($topLevelNav, true);
	}, 100);
	$('#organizer_topLevelNavScrollDown').mousehold(function() {
		zenarioO.scrollTopLevelNav($topLevelNav, false);
	}, 100);
	zenarioO.setTopLevelNavScrollStatus($topLevelNav);
};

zenarioO.scrollTopLevelNav = function($topLevelNav, up) {
	$topLevelNav.scrollTop($topLevelNav.scrollTop() + (up? -50 : 50));
	zenarioO.setTopLevelNavScrollStatus($topLevelNav);
};

zenarioO.setTopLevelNavScrollStatus = function($topLevelNav) {
	if (!$topLevelNav) {
		$topLevelNav = $('#organizer_topLevelNav');
	}
	
	var scrollLength = $('#organizer_topLevelNavInner').outerHeight() - $topLevelNav.innerHeight();
	
	if (scrollLength <= 0) {
		$('#organizer_topLevelNavScroll').hide();
	} else {
		$('#organizer_topLevelNavScroll').show();
		
		var scrollTop = $topLevelNav.scrollTop();
		
		if (scrollTop > 0) {
			$('#organizer_topLevelNavScrollUp').addClass('organizer_scrollActive');
		} else {
			$('#organizer_topLevelNavScrollUp').removeClass('organizer_scrollActive');
		}
		
		if (scrollTop < scrollLength) {
			$('#organizer_topLevelNavScrollDown').addClass('organizer_scrollActive');
		} else {
			$('#organizer_topLevelNavScrollDown').removeClass('organizer_scrollActive');
		}
	}
};


zenarioO.setDataAttributes = function(tuix, mergefields) {
	foreach (tuix as var prop) {
		if (prop.substr(0, 4) == 'data') {
			mergefields[prop] = tuix[prop];
		}
	}
};

zenarioO.setTopRightButtons = function() {
	
	delete zenarioO.map.top_right_buttons.ord;
	var sortedTopRightButtons = zenarioO.getSortedIdsOfTUIXElements(zenarioO.map.top_right_buttons);
	
	var bi = -1,
		button,
		buttons = [],
		disabled,
		i, itemNo;
	
	foreach (sortedTopRightButtons as itemNo => i) {
		button = zenarioO.map.top_right_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button);
		buttons[++bi] = zenarioO.setButtonAction('zenarioO.topRightButtonClick', button, disabled, i, undefined, undefined, '');
	}
	
	//Add parent/child relationships
	zenarioA.setButtonKin(buttons);
	
	$('#organizer_top_right_buttons').html(zenarioA.microTemplate('zenario_organizer_top_right_button', buttons));
	
	zenarioA.tooltips('#organizer_top_right_buttons a[title]', {position: {my: 'right center', at: 'left center', collision: 'flipfit'}, show: false, hide: false});

};

zenarioO.setButtons = function() {
	
	var selectedItems = zenarioO.pi.returnSelectedItems();
	zenarioO.itemsSelected = 0;
	
	foreach (selectedItems as var id) {
		++zenarioO.itemsSelected;
	}
	
	zenarioO.pi.showButtons(
		$('#organizer_buttons'));
	
	zenarioO.setChooseButton();
	
	//if (!get('organizer_rightColumnOptions')) {
	//	return;
	//}
	//
	//if (zenarioO.changingHierarchy) {
	//	$('#organizer_rightColumnOptions').hide();
	//	$('#organizer_rightColumnApplyCancel').show();
	//} else {
	//	$('#organizer_rightColumnOptions').show();
	//	$('#organizer_rightColumnApplyCancel').hide();
	//	
	//	delete zenarioO.upload;
	//	delete zenarioO.uploadIsItemLevel;
	//	if (zenarioO.itemsSelected) {
	//		zenarioO.setItemButtons(transition);
	//	} else {
	//		zenarioO.setCollectionButtons(transition);
	//	}
	//	zenarioO.setUploads();
	//	zenarioO.setChooseButton();
	//}
};

zenarioO.getQuickFilters = function() {
	
	var bi = -1,
		button,
		buttons = [],
		disabled,
		i, itemNo;
	
	foreach (zenarioO.sortedQuickFilterButtons as itemNo => i) {
		button = zenarioO.tuix.quick_filter_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button);
		buttons[++bi] = zenarioO.setButtonAction('zenarioO.toggleQuickFilter', button, disabled, i, undefined, undefined, '');
		
		buttons[bi].enabled = zenarioO.quickFilterEnabled(i);
	}
	
	//Add parent/child relationships
	zenarioA.setButtonKin(buttons);
	
	return buttons;
};

//zenarioO.setCollectionButtons = function(transition) {
zenarioO.getCollectionButtons = function() {
	
	var bi = -1,
		button,
		buttons = [],
		disabled,
		i, itemNo;
	
	foreach (zenarioO.sortedCollectionButtons as itemNo => i) {
		button = zenarioO.tuix.collection_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		//Hide the export to csv button if there are no rows
		if (!zenarioO.searchMatches && (engToBoolean(button.do_csv_export) || engToBoolean(button.hide_when_no_search_results))) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button);
		buttons[++bi] = zenarioO.setButtonAction('zenarioO.collectionButtonClick', button, disabled, i);
	}
	
	//Add parent/child relationships
	zenarioA.setButtonKin(buttons);
	
	return buttons;
};

zenarioO.buttonsPrevHTML = '';
zenarioO.fadeOutLastButtons = function(html, item, transition) {
	if (!get('organizer_rightColumnOptions')) {
		return;
	}
	
	var $collectionButtons = $('#organizer_collectionToolbar'),
		$collectionButtonsHTML = $('#organizer_collectionToolbarButtons'),
		$itemButtons = $('#organizer_itemToolbar'),
		$itemButtonsHTML = $('#organizer_itemToolbarButtons'),
		$prevButtons = $('#organizer_previousToolbar'),
		$prevButtonsHTML = $('#organizer_previousToolbarButtons');
	
	if ($collectionButtons.is(':visible')) {
		$collectionButtons.clearQueue().hide();
	}
	
	if ($itemButtons.is(':visible')) {
		$itemButtons.clearQueue().hide();
	}
	
	if (item) {
		$itemButtonsHTML.html(html);
	} else {
		$collectionButtonsHTML.html(html);
	}
	
	$prevButtons.clearQueue().hide();
	
	if (!transition || zenarioO.buttonsPrevHTML == html) {
		//Either transitions are not enabled, or the HTML hasn't actually changed therefore there's not need to show one
		if (item) {
			$itemButtons.show();
		} else {
			$collectionButtons.show();
		}
	
	} else {
		if (zenarioO.buttonsPrevHTML) {
			//Fade out the previous buttons
			$prevButtonsHTML.html(zenarioO.buttonsPrevHTML);
			$prevButtons.show().fadeOut({
				duration: 250,
				easing: 'zenarioLinearWithDelayAfterwards',
				complete: function() { $prevButtonsHTML.html(''); }
			});
		}
		
		//Fade in the new buttons
		if (item) {
			$itemButtons
				.css('animation', 'none')
				.fadeIn({
					duration: 250,
					easing: 'zenarioLinearWithDelayAfterwards',
					complete: function() { $itemButtons.css('animation', ''); $itemButtons.addClass('yellow_flash'); }
				});
		} else {
			$collectionButtons
				.css('animation', 'none')
				.fadeIn({
					duration: 250,
					easing: 'zenarioLinearWithDelay',
					complete: function() { $collectionButtons.css('animation', ''); $collectionButtons.addClass('white_flash'); }
				});
		}
	}
	
	zenarioO.buttonsPrevHTML = html;
};

zenarioO.showCollectionButtons = function(html, transition) {
	zenarioO.fadeOutLastButtons(html, false, transition);
};

zenarioO.hideCollectionButtons = function(transition) {
	zenarioO.fadeOutLastButtons('', false, transition);
};

zenarioO.showItemButtons = function(html, transition) {
	zenarioO.fadeOutLastButtons(html, true, transition);
};

zenarioO.hideItemButtons = function(transition) {
	zenarioO.fadeOutLastButtons('', true, transition);
};



//zenarioO.setItemButtons = function(transition) {
zenarioO.getItemButtons = function() {
	
	var i,
		bi = -1,
		button,
		buttons = [],
		parent,
		disabled,
		i, itemNo,
		selectedItems = zenarioO.pi.returnSelectedItems(),
		id = zenarioO.getKeyId(true),
		item = id && zenarioO.tuix.items && zenarioO.tuix.items[id];
	
	if (zenarioO.itemsSelected > 0) {
		foreach (zenarioO.sortedItemButtons as itemNo => i) {
			button = zenarioO.tuix.item_buttons[i];
			
			//Check if this button only works with single items, and then don't show it if multiple items are selected
			var maxItems = 1, 
				minItems = 1;
			
			if (engToBoolean(button.multiple_select)) {
				maxItems = button.multiple_select_max_items;
				
				if (engToBoolean(button.multiple_select_only)) {
					minItems = 2;
				}
			}
			
			if (zenarioO.itemsSelected < minItems || (maxItems && zenarioO.itemsSelected > maxItems)) {
				continue;
			}
			
			//Check if this button is hidden
			if (zenarioO.checkButtonHidden(button, selectedItems)) {
				continue;
			}
			
			if (!zenarioO.checkTraits(button, selectedItems)) {
				continue;
			}
			
			disabled = zenarioO.checkDisabled(button, selectedItems);
			buttons[++bi] = zenarioO.setButtonAction('zenarioO.itemButtonClick', button, disabled, i, undefined, item);
			
			
			buttons[bi].label = zenarioO.applyMergeFieldsToLabel(
				ifNull(button.label, button.name),
				false, true,
				button.multiple_select_label
			);
			
			if (disabled && button.disabled_tooltip) {
				buttons[bi].tooltip = zenarioO.applyMergeFieldsToLabel(button.disabled_tooltip, false, true);
			
			} else {
				buttons[bi].tooltip = zenarioO.applyMergeFieldsToLabel(
					button.tooltip,
					false, true,
					button.multiple_select_tooltip
				);
			}
		}
	}
	
	//Add parent/child relationships
	zenarioA.setButtonKin(buttons);
	
	return buttons;
};



zenarioO.setButtonAction = function(funName, button, disabled, buttonId, itemId, item, defaultCSS) {
	
	if (defaultCSS === undefined) {
		defaultCSS = 'label_without_icon';
	}
	
	var merge = {
		id: buttonId,
		tuix: button,
		css_class: button.css_class || defaultCSS,
		label: button.label || button.name,
		disabled: disabled
	};
	
	if (disabled) {
		merge.onclick = "return false;";
		
	} else if (button.navigation_path) {
		if (item && item.navigation_path) {
			merge.href = '#/' + item.navigation_path;
		} else {
			merge.href = '#/' + button.navigation_path;
		}
	
	} else if (button.frontend_link) {
		if (item && item.frontend_link) {
			merge.href = zenarioO.parseReturnLink(item.frontend_link);
		} else {
			merge.href = zenarioO.parseReturnLink(button.frontend_link);
		}
	
	} else if (button.onclick) {
		merge.onclick = button.onclick;
		
		//If this button is for one specific item (e.g. an inline button), ensure that item is selected first
		if (itemId && item) {
			merge.onclick = "zenarioO.selectItems('" + jsEscape(itemId) + "'); zenario.stop(event); " + merge.onclick;
		}
	
	} else {
		merge.onclick = funName + "('" + jsEscape(buttonId) + (itemId === undefined? '' : "', '" + jsEscape(itemId)) + "'); return false;";
	}
	
	if (disabled && button.disabled_tooltip) {
		merge.tooltip = button.disabled_tooltip;
	} else {
		merge.tooltip = button.tooltip;
	}
	
	return merge;
};


//Check to see whether a button should be disabled
zenarioO.checkDisabled = function(button, items) {
	var id,
		singleItem = undefined,
		singleItemId = undefined;
	
	if (engToBoolean(button.disabled)) {
		return true;
	}
	
	//Check to see if there is only one item selected
	if (items) {
		foreach (items as id) {
			if (singleItemId === undefined) {
				singleItemId = id;
				singleItem = zenarioO.tuix.items && zenarioO.tuix.items[id];
			} else {
				singleItemId =
				singleItem = undefined;
				break;
			}
		}
	}
	
	if (button.disabled_if !== undefined) {
		if (zenarioA.eval(button.disabled_if, button, singleItem, singleItemId)) {
			return true;
		}
	}
	
	//Check whether an item button with the visible_if_for_all_selected_items/visible_if_for_any_selected_items
	//properties should be visible
	if (items && button.disabled_if_for_any_selected_items !== undefined) {
		foreach (items as id) {
			if (zenarioA.eval(button.disabled_if_for_any_selected_items, button, zenarioO.tuix.items[id], id)) {
				return true;
			}
		}
	}
	
	if (items && button.disabled_if_for_all_selected_items !== undefined) {
		foreach (items as id) {
			if (!zenarioA.eval(button.disabled_if_for_all_selected_items, button, zenarioO.tuix.items[id], id)) {
				return false;
			}
		}
		
		return true;
	}
	
	return false;
};

zenarioO.checkButtonHidden = function(button, items) {
	
	var id,
		singleItem = undefined,
		singleItemId = undefined,
		hide_on_refiner;
	
	//Check to see if there is only one item selected
	if (items) {
		foreach (items as id) {
			if (singleItemId === undefined) {
				singleItemId = id;
				singleItem = zenarioO.tuix.items && zenarioO.tuix.items[id];
			} else {
				singleItemId =
				singleItem = undefined;
				break;
			}
		}
	}
	
	//Check if this button is hidden
	if (zenarioA.hidden(button, true, singleItem, singleItemId)) {
		return true;
	}
	
	//Check if this button should be hidden in quick/select mode (or hidden if not in quick/select mode)
	//Also, automatically hide a button in quick/select mode if it is a front-end link
	if ((window.zenarioONotFull && button.frontend_link)
	||  (window.zenarioOQuickMode && engToBoolean(button.hide_in_quick_mode))
	||  (window.zenarioOSelectMode && engToBoolean(button.hide_in_select_mode))
	||  (!window.zenarioOQuickMode && engToBoolean(button.quick_mode_only) && !(window.zenarioOSelectMode && engToBoolean(button.select_mode_only)))
	||  (!window.zenarioOSelectMode && engToBoolean(button.select_mode_only) && !(window.zenarioOQuickMode && engToBoolean(button.quick_mode_only)))) {
		return true;
	}
	
	//Check if this button should only be shown in a certain refiner
	if (button.only_show_on_refiner) {
		if (!zenarioO.refiner) {
			return true;
		
		} else {
			if (typeof button.only_show_on_refiner == 'object') {
				if (!engToBoolean(button.only_show_on_refiner[zenarioO.refiner.name])) {
					return true;
				}
			} else {
				if (zenarioO.refiner.name != button.only_show_on_refiner) {
					return true;
				}
			}
		}
	}
	
	//Check if this button should never be shown in a certain refiner
	if (zenarioO.refiner && (hide_on_refiner = button.hide_on_refiner)) {
		//I want to check if someone has entered true for hide_on_refiner, but need to deal
		//with the fact that true sometimes converted to a number 1, or a string "1".
		//Thankfully this is easy to do in JavaScript, as 1*"1" === 1,
		//but 1*"any-other-string" !== 1
		if (1 === 1 * hide_on_refiner) {
			return true;
		
		} else if (typeof hide_on_refiner == 'object') {
			if (engToBoolean(hide_on_refiner[zenarioO.refiner.name])) {
				return true;
			}
		} else {
			if (zenarioO.refiner.name == hide_on_refiner) {
				return true;
			}
		}
	}
	
	//Some buttons should not be shown when there is a search or a filter set
	if (zenarioO.filteredView && engToBoolean(button.hide_on_filter)) {
		return true;
	}
	
	//In select mode, don't show a link to a panel that we can't reach due to a max-path restriction
	if (zenarioO.pathNotAllowed(button.link)) {
		return true;
	}
	
	return false;
};

//Check to see whether an item button should be visible or hidden based on logic specific to item buttons
zenarioO.checkTraits = function(button, items) {
	var id, met;
	
	//Catch the case where we get passed an undefined rather than an object
	//(Note that no conditions means whatever this is should be shown!)
	if (!button) {
		return true;
	}
	
	//Check whether an item button with the visible_if_for_all_selected_items/visible_if_for_any_selected_items
	//properties should be visible
	if (button.visible_if_for_all_selected_items !== undefined) {
		foreach (items as id) {
			if (!zenarioA.eval(button.visible_if_for_all_selected_items, button, zenarioO.tuix.items[id], id)) {
				return false;
			}
		}
	}
	
	met = false;
	if (button.visible_if_for_any_selected_items !== undefined) {
		foreach (items as id) {
			if (zenarioA.eval(button.visible_if_for_any_selected_items, button, zenarioO.tuix.items[id], id)) {
				met = true;
				break;
			}
		}
		
		if (!met) {
			return false;
		}
	}
	
	
	//Old deprecated traits logic
	//Check if that button requires a trait or a column to be set, or not to be set, in order to be shown.
	//Note that traits use an engToBoolean() check but columns just use a normal boolean check.
	//(i.e. 'No' or 'False' as strings would be true).
	met = true;
	var checks = {
			traits: {
				condition: function(id, trait) {
					return zenarioO.tuix.items[id].traits && engToBoolean(zenarioO.tuix.items[id].traits[trait]); 
				}
			},
			without_traits: {
				condition: function(id, trait) {
					return !zenarioO.tuix.items[id].traits || !engToBoolean(zenarioO.tuix.items[id].traits[trait]);
				}
			},
			with_columns_set: {
				condition: function(id, column) {
					return engToBoolean(zenarioO.tuix.items[id][column]);
				}
			},
			without_columns_set: {
				condition: function(id, column) {
					return !engToBoolean(zenarioO.tuix.items[id][column]);
				}
			}
		};
	checks.one_with_traits = $.extend(true, {}, checks.traits, {one: true});
	checks.one_without_traits = $.extend(true, {}, checks.without_traits, {one: true});
	checks.one_with_columns_set = $.extend(true, {}, checks.with_columns_set, {one: true});
	checks.one_without_columns_set = $.extend(true, {}, checks.without_columns_set, {one: true});
	
	//Loop through each check
	foreach (checks as var check) {
		if (button[check]) {
			//Loop through each item, checking whether they match.
			met = !checks[check].one;
			foreach (items as id) {
				
				var thisItemMet = true;
				foreach (button[check] as var trait) {
					if (engToBoolean(button[check][trait])) {
						if (!checks[check].condition(id, trait)) {
							thisItemMet = false;
							break;
						}
					}
				}
				
				if (checks[check].one) {
					//For "at least one" logic, we'll assume the button doesn't match until we find one example
					//that does.
					if (thisItemMet) {
						met = true;
						break;
					}
				
				} else {
					//For "all must match" logic, we'll assume the button matches until we find one example
					//that doesn't, at which point we'll break out of the loop.
					if (!thisItemMet) {
						met = false;
						break;
					}
				}
			}
		}
		if (!met) {
			break;
		}
	}
	
	return met;
};

//Shortcut to call the above function for just one item
zenarioO.checkItemPickable = function(id) {
	var items = {};
	items[id] = true;
	return zenarioO.checkTraits(window.zenarioOSelectObject, items)
		&& !zenarioO.checkDisabled(window.zenarioOSelectObject, items);
};


zenarioO.getInlineButtons = function(id) {
	
	var bi = -1,
		buttons = [],
		disabled,
		i, itemNo, ids;
	
	foreach (zenarioO.sortedInlineButtons as itemNo => i) {
		button = zenarioO.tuix.inline_buttons[i],
		ids = {};
		ids[id] = true;
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button, ids)) {
			continue;
		}
		
		if (!zenarioO.checkTraits(button, ids)) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button, ids);
		buttons[++bi] = zenarioO.setButtonAction('zenarioO.inlineButtonClick', button, disabled, i, id, zenarioO.tuix.items[id]);
		
		if (buttons[bi].tooltip) {
			buttons[bi].tooltip = zenarioO.applyMergeFields(buttons[bi].tooltip, false, id);
		}
		if (buttons[bi].label) {
			buttons[bi].label = zenarioO.applyMergeFields(buttons[bi].label, false, id);
		}
	}
	
	if (buttons.length) {
		return buttons;
	} else {
		return undefined;
	}
};



zenarioO.chooseButtonActive = function() {
	return window.zenarioOSelectMode
		&& (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false);
};

zenarioO.setChooseButton = function() {
	//Enable or disable the "Choose" button
	if (window.zenarioOSelectMode) {
		var choosePhrase = ifNull(window.zenarioOChoosePhrase, phrase.choose),
			disabled = true,
			className = 'submit_disabled',
			selectedItems = zenarioO.pi.returnSelectedItems();
		
		if (zenarioO.chooseButtonActive()
		 && zenarioO.checkTraits(window.zenarioOSelectObject, selectedItems)
		 && !zenarioO.checkDisabled(window.zenarioOSelectObject, selectedItems)
		 && (zenarioO.itemsSelected > 0 || window.zenarioOAllowNoSelection)
		 && (zenarioO.itemsSelected <= 1 || window.zenarioOMultipleSelect)) {
			disabled = false;
			className = window.zenarioOChooseButtonActiveClass? 'submit_selected' : 'submit';
			
			if (zenarioO.itemsSelected == 0 && window.zenarioOAllowNoSelection && window.zenarioONoSelectionChoosePhrase) {
				choosePhrase = window.zenarioONoSelectionChoosePhrase;
			
			} else if (zenarioO.itemsSelected > 1 && window.zenarioOChooseMultiplePhrase) {
				choosePhrase = window.zenarioOChooseMultiplePhrase;
			}
		
		} else {
			if (window.zenarioOAllowNoSelection && window.zenarioONoSelectionChoosePhrase) {
				choosePhrase = window.zenarioONoSelectionChoosePhrase;
			}
		}
		
		get('organizer_choose_button').disabled = disabled;
		get('organizer_choose_button').className = className;
		get('organizer_choose_button').value = htmlspecialchars(choosePhrase);
	}
};

zenarioO.getCurrentPage = function() {
	return zenarioO.page;
};

zenarioO.getPageCount = function() {
	if (zenarioO.thisPageSize
	 && zenarioO.searchMatches !== undefined) {
		zenarioO.pageCount = Math.ceil(zenarioO.searchMatches / zenarioO.thisPageSize);
	} else {
		zenarioO.pageCount = 1;
	}
	
	return zenarioO.pageCount;
};


zenarioO.setPanelTitle = function() {
	if (zenarioO.pi
	 && zenarioO.tuix
	 && get('organizer_rightColumnTitle')) {
		get('organizer_rightColumnTitle').style.display = '';
		
		var title = zenarioO.pi.returnPanelTitle();
		
		if (zenarioO.tuix.item && zenarioO.tuix.item.css_class) {
			get('organizer_panelTitle').innerHTML =
				'<div class="listicon organizer_item_image ' + zenarioO.tuix.item.css_class + '"></div><div>' + htmlspecialchars(title) + '</div>';
		} else {
			get('organizer_panelTitle').innerHTML = htmlspecialchars(title);
		}
		
		var width = Math.max(0, ifNull($('#organizer_rightColumnTitle').width(), 0, 0) - 75 - ifNull($('#organizer_collectionToolbar').width(), 90));
		
		//get('organizer_panelTitle').style.fontSize = Math.min(18, 1.6 * Math.round((width) / ('' + title).length)) + 'px';
		//$('#organizer_panelTitle').width(width);
		
		zenarioA.setTooltipIfTooLarge('#organizer_panelTitle', title, zenarioA.tooltipLengthThresholds.organizerPanelTitle);
		
		if (zenarioA.isFullOrganizerWindow) {
			document.title = zenarioO.pageTitle + phrase.colon + title;
		}
	}
};


zenarioO.setTrash = function () {
	if (!window.zenarioOSelectMode && zenarioO.tuix && zenarioO.tuix.trash) {
		get('organizer_trash_button').style.display = '';
		get('organizer_trash_button').className =
			engToBoolean(zenarioO.tuix.trash.empty)?
				ifNull(zenarioO.tuix.trash.empty_css_class, 'trash_button_empty')
			 :	ifNull(zenarioO.tuix.trash.full_css_class, 'trash_button_full');
		
		zenarioA.tooltips('#organizer_trash_button a', {
			position: {
				my: 'center bottom-12',
				at: 'center top',
				collision: 'flipfit',
				using: zenario.tooltipsUsing
			},
			items: '*',
			content:
				engToBoolean(zenarioO.tuix.trash.empty)?
					ifNull(zenarioO.tuix.trash.empty_tooltip, phrase.viewTrash)
				 :	ifNull(zenarioO.tuix.trash.full_tooltip, phrase.viewTrash)
		});
		
	} else {
		get('organizer_trash_button').style.display = 'none';
	}
};



zenarioO.getItemCSSClass = function (i) {
	if (zenarioO.tuix.items[i].css_class) {
		return zenarioO.tuix.items[i].css_class;
	} else if (zenarioO.tuix.item) {
		return zenarioO.tuix.item.css_class;
	} else {
		return undefined;
	}
};

//Send a signal to any Modules on the page to fill the lower-left of Organizer
//This should return an array of arrays of things in the format:
	//[[[html, ord], [html, ord], ... ], ... ]
//Flatten this, sort this, then output this.
zenarioO.fillLowerLeft = function() {
	var i, j,
		a2 = [],
		a = zenario.sendSignal('fillOrganizerLowerLeft');
	
	a.push([[zenarioA.microTemplate('zenario_organizer_lower_left_column', {}), 0]]);
	
	foreach (a as i) {
		foreach (a[i] as j) {
			a2.push(a[i][j]);
		}
	}
	
	a2.sort(zenarioA.sortArray);
	
	foreach (a2 as i) {
		a2[i] = a2[i][0];
	}
	
	return a2.join('\n');
};


zenarioO.closeSelectMode = function() {
	zenarioO.closeInfoBox();
	
	if (zenarioA.isFullOrganizerWindow) {
		windowParent.zenarioA.closeBox('AdminOrganizer', true);
		zenarioO.go(zenarioO.defaultPathInIframePreload);
		
		zenarioA.showAJAXLoader();
		zenarioO.firstLoaded = false;
	
	} else {
		zenarioO.go(zenarioO.defaultPathInIframePreload);
		zenarioA.closeBox('og');
		zenarioO.firstLoaded = false;
	}
	
	return false;
};

zenarioO.choose = function() {
	
	//Stop admins making choices on outdated panels
	if (zenarioO.lastSuccessfulGoNum != zenarioO.goNum) {
		return;
	}
	
	if (windowParent
	 && window.zenarioOSelectMode
	 && (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)
	 && (zenarioO.itemsSelected > 0 || window.zenarioOAllowNoSelection)
	 && (zenarioO.itemsSelected <= 1 || window.zenarioOMultipleSelect)) {
		
		//Disable the current panel/page from refreshing
		zenarioO.stopRefreshing();
		
		var key = zenarioO.getKey(true),
			path = zenarioO.path,
			panel = $.extend(true, {}, zenarioO.tuix),
			row,
			selectedItems = zenarioO.pi.returnSelectedItems(),
			callback = false;
		
		key._items = {};
		foreach (selectedItems as var i) {
			key._items[i] = true;
			
			if (!window.zenarioOMultipleSelect) {
				break;
			}
		}
		
		if (!window.zenarioOMultipleSelect && key.id) {
			row = panel.items[key.id];
			row.__label_tag__ = zenarioO.defaultSortColumn;
		} else {
			row = {};
		}
		
		zenarioO.closeSelectMode();
		
		//Check to see if the original caller passed a callback function for us
		if (_.isFunction(window.zenarioOCallbackObject)) {
			callback = window.zenarioOCallbackObject;
		} else if (_.isFunction(window.zenarioOCallbackFunction)) {
			callback = window.zenarioOCallbackFunction;
		}
		
		if (callback) {
			//If they did, call it
			if (window.zenarioOMultipleSelect) {
				callback(path, key, undefined, panel);
			} else {
				callback(path, key, row, panel);
			}
		} else {
			//If not look for the named object/method in the callback window
			if (window.zenarioOMultipleSelect) {
				windowParent[window.zenarioOCallbackObject][window.zenarioOCallbackFunction](path, key, undefined, panel);
			} else {
				windowParent[window.zenarioOCallbackObject][window.zenarioOCallbackFunction](path, key, row, panel);
			}
			
			//N.b. When we first added the callback variable above we tried to use the code:
				//callback = windowParent[window.zenarioOCallbackObject][window.zenarioOCallbackFunction];
				//...
				//callback(path, key, row, panel);
			//However this called the function with the wrong scope and we can't work out why.
		}
	}
};



//
//	Some specific functions
//

//An info box with helpful things for Module Developers
zenarioO.infoBox = function(el) {
	
	var html = zenarioA.microTemplate('zenario_organizer_debug_info', {}),
		width = 600,
		buttonWidth = 28,//$('.zenario_debug').width()
		buttonHeight = 28;//$('.zenario_debug').width()
	
	//$('#zenario_debug_infobox').html(html).show();
	zenarioA.openBox(html, 'zenario_fbDebugInfoBox', 'DebugInfoBox', el, 600, (buttonWidth - width) / 2, buttonHeight, false, false, undefined, undefined, undefined, undefined, false, false);
	//(html, className, n, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement)

	
	//get('zenario_fbAdminInfoBox').innerHTML = html;
	//zenario.addJQueryElements('#zenario_fbAdminInfoBox ', true);
};

zenarioO.closeInfoBox = function() {
	zenarioA.closeBox('DebugInfoBox');
};




//Set/update the html in the "My work in progress" dropdown
zenarioO.yourWorkInProgressLastUpdated = 0;
zenarioO.updateYourWorkInProgress = function() {
	
	//Only update once every so often, to avoid spamming AJAX requests just because the admin keeps moving their mouse over the button
	var ms = (new Date()).getTime(),
		$dropdown,
		url;
	
	if ((zenarioO.yourWorkInProgressLastUpdated - ms < -zenarioO.yourWorkInProgressLastUpdateFrequency)
	 && ($dropdown = $('#zenario_ywip_dropdown'))) {
		
		zenarioO.yourWorkInProgressLastUpdated = ms;
		$dropdown.removeClass('zenario_ywip_loaded').addClass('zenario_ywip_loading');
		
		url =
			URLBasePath + 'zenario/admin/organizer.ajax.php' +
			'?path=zenario__content/panels/content' +
			'&_sort_col=last_modified_datetime' +
			'&_sort_desc=1' +
			'&_start=0' +
			'&_limit=' + zenarioO.yourWorkInProgressItemCount +
			'&refinerId=' +
			'&refinerName=your_work_in_progress' +
			'&refiner__your_work_in_progress=' +
			'&_get_item_data=1';
		
		zenario.ajax(url, false, true, true).after(function(WiP) {
			var html = zenarioA.microTemplate('zenario_ywip', WiP);
			$dropdown.removeClass('zenario_ywip_loading').addClass('zenario_ywip_loaded').html(html);
			
		});
	}
};
$(document).ready(function() { setTimeout(zenarioO.updateYourWorkInProgress, 100) } );




//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioO.sizing = false;
zenarioO.lastSize = false;
zenarioO.size = function(refresh) {
			
	var headerHeight = 46,
		footerHeight = 57,
		contentGap = 40,
		titleHeight = 0,
		buttonsHeight = 30,
		bordersWidth = 2,
		rightColumnContentBorderWidth = 2,
		headerHeight = 35,
		headerToolbar = 55,
		bottomWrapHeight = 45,
		leftColAdjustment,
		domOrgWrapper = get('zenario_fbog'),
		$zenario_fbog = $(domOrgWrapper),
		width, height, el;
	
	
	if (zenarioO.sizing) {
		clearTimeout(zenarioO.sizing);
	}
	
	if (!get('organizer__box_wrap')) {
		return;
	}
	
	//Try to get the width/height of the window
	if (!zenarioA.isFullOrganizerWindow) {
		width = Math.floor($zenario_fbog.width());
		height = Math.floor($zenario_fbog.height());
	} else {
		width = Math.floor($(window).width());
		height = Math.floor($(window).height());
	}
	
	if (width && height) {
		//Set a minimum width/height
		if (width < 550) {
			width = 550;
		}
		if (height < 400) {
			height = 400;
		}
		
		if (refresh || zenarioO.lastSize != width + 'x' + height) {
			
			//Check whether we should show the left-hand-nav
			if (zenarioO.pi) {
				zenarioO.showLeftColumn = zenarioO.pi.returnShowLeftColumn();
			} else {
				//By default, if this is full mode then show the left hand nav, and if not, then hide it.
				zenarioO.showLeftColumn = !window.zenarioONotFull;
			}
			
			var domPanel = get('organizer_rightColumnContent'),
				domIcons = get('organizer_lowerLeftColumn'),
				domFooter = get('organizer_lowerMiddleColumn'),
				domChooseButtonWrap = get('organizer_lowerRightColumn'),
				$header = $('#organizer_header'),
				$panel = $(domPanel),
				$footer = $(domFooter),
				$buttons = $('#organizer_buttons'),
				$bottomWrap = $('#organizer_bottomWrap');
			
			if (!$header.is(':visible')) {
				headerToolbar = 0;
			}
			if (!$buttons.is(':visible')) {
				buttonsHeight = 0;
			}
			if (!$bottomWrap.is(':visible')) {
				footerHeight = 0;
			}

			
			if (domOrgWrapper) {
				domOrgWrapper.className = zenarioA.getSKBodyClass(window, zenarioO.pi && zenarioO.pi.panelType);
			}
			
			//This line fixes a bug where the height of the floating div keeps changing when Organizer is opened
			//by specifically setting it to what it was read as
			if (!zenarioA.isFullOrganizerWindow) {
				Math.floor($zenario_fbog.height(height));
			}
			
			get('organizer_rightColumn').style.height =
			get('organizer_preloader_circle').style.height = (1*height - headerHeight - footerHeight) + 'px';
			get('organizer_leftColumn').style.height = ((1*height - headerHeight - footerHeight) - 2) + 'px';
			get('organizer_Outer').style.height = (1*height - headerHeight) + 'px';
			
			if (get('organizer_colsortfields')) {
				if (zenario.browserIsIE(6)) {
					get('organizer_colsortfields').style.height = (1*height - headerHeight - 130) + 'px';
				} else {
					get('organizer_colsortfields').style.maxHeight = (1*height - headerHeight - 130) + 'px';
				}
			}
			
			var rightColumnContentHeight = ((1*height - headerHeight - footerHeight - contentGap - buttonsHeight - bordersWidth - zenarioO.columnPadding + titleHeight - headerToolbar) - 13);
			if (domPanel) {
				domPanel.style.height = rightColumnContentHeight + 'px';
			}
			
			if (zenarioO.showLeftColumn) {
				leftColAdjustment = 204;
				get('organizer_leftColumn').style.display = get('organizer_leftColumnOuter').style.display = '';
			} else {
				leftColAdjustment = 0;
				get('organizer_leftColumn').style.display = get('organizer_leftColumnOuter').style.display = 'none';
			}

			get('organizer__box_top').style.width =
			get('organizer__box_wrap').style.width =
			get('organizer__box_center').style.width =
			get('organizer__box_bottom').style.width = (width - 0) + 'px';
			
			get('organizer_Outer').style.width = (width - 22) + 'px';
			get('organizer_wrapInner_bm').style.width = (width - 32) + 'px';
			get('organizer_wrapOuter_tm').style.width = (width - 0) + 'px';
			
			get('organizer_rightColumn').style.width = get('organizer_preloader_circle').style.width = (width - 30 - leftColAdjustment) + 'px';
			if (get('organizer_rightColumnTitle')) get('organizer_rightColumnTitle').style.width = (width - 30 - leftColAdjustment - 2) + 'px';
			if (domPanel) domPanel.style.width = (width - 30 - leftColAdjustment - bordersWidth) + 'px';
			
			
			if (window.zenarioOSelectMode) {
				get('organizer_choose_button').style.display = 'inline';
			} else {
				get('organizer_choose_button').style.display = 'none';
			}
			
			if (width <= 825) {
				domIcons.style.width = "40%";
				domFooter.style.width = "49%";
				domChooseButtonWrap.style.width = "10%";
			} else if (width <= 965) {
				domIcons.style.width = "35%";
				domFooter.style.width = "49%";
				domChooseButtonWrap.style.width = "15%";
			} else if(width <= 1155) {
				domIcons.style.width = "30%";
				domFooter.style.width = "49%";
				domChooseButtonWrap.style.width = "20%";
			} else if(width >= 1155) {
				domIcons.style.width = "25%";
				domFooter.style.width = "49%";
				domChooseButtonWrap.style.width = "25%";
			}
			
			zenarioO.setPanelTitle();
			
			
			
			if (zenarioO.pi) {
				zenarioO.pi.sizePanel($header, $panel, $footer, $buttons);
			}
			
			
			zenarioO.lastSize = width + 'x' + height;
			
			zenarioO.setTopLevelNavScrollStatus();
		}
	}
	
	//Stop selection/highlighting in IE
	if (zenario.browserIsIE()) {
		if (get('organizer_rightColumnContent')) {
			if (zenario.browserIsIE(8)) {
				if (el = get('organizer_Outer')) {
					el.onselectstart =
						function() {
							return false;
						};
				}
			
			} else {
				if (el = get('organizer_leftColumnOuter')) {
					el.onselectstart =
						function() {
							return false;
						};
				}
				if (el = get('organizer_itemCountWrap')) {
					el.onselectstart =
						function() {
							return false;
						};
				}
				if (el = get('organizer_rightColumn')) {
					el.onselectstart =
						function() {
						return !(zenarioO.itemsSelected > 1);
					};
				}
			}
		}
	}
	
	
	zenarioO.sizing = setTimeout(zenarioO.size, 500);
};

});