/*
 * Copyright (c) 2014, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and "inc.js.php" files for step (3).
*/


window.zenarioNewO = function(skNum, topLeftHTML, topRightHTML) {
	(function(
		zenarioO,
		URLBasePath,
		window, document,
		zenario, zenarioA, zenarioTab, zenarioAT,
		get, engToBoolean, htmlspecialchars, ifNull, jsEscape, phrase,
		undefined) {
			"use strict";


zenarioO.encapName = 'zenarioO';


//Constants
zenarioO.pageSize = 50;
zenarioO.getItemLinkTimeoutTime = 7000;

//(Periodic refresh is now disabled)
//zenarioO.periodicRefreshTime = 120000;

zenarioO.yourWorkInProgressLastUpdateFrequency = 60000;
zenarioO.yourWorkInProgressItemCount = 10;

zenarioO.numberOfAdvancedSearches = 8;

zenarioO.itemDoubleClickTime = 500;
zenarioO.searchDelayTime = 700;
zenarioO.firstLoaded = false;

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





//Go to the "Content by Layout" panel by default
zenarioO.defaultPath = 'zenario__content/nav/content/panel';
zenarioO.defaultPathInIframePreload = 'loading_message/panel';






zenarioO.open = function(className, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement) {
	if (!topLeftHTML) {
		topLeftHTML = '';
	}
	if (!topRightHTML) {
		topRightHTML =
			'<div class="organizer_close_button">' +
				'<a onclick="zenarioO.closeSelectMode();"></a>' +
			'</div>';
	}
	
	var html = zenarioA.microTemplate('zenario_organizer', {topLeftHTML: topLeftHTML, topRightHTML: topRightHTML});
	
	zenarioA.openBox(html, className, 'sk' + skNum, e, width, left, top, disablePageBelow, overlay, draggable, resizable, padding, maxHeight, rightCornerOfElement, bottomCornerOfElement);
};



//Start up the JavaScript engine for the Storekeeper as soon as the page has loaded
zenarioO.init = function(reload) {
	
	//Unless the reload flag is set, don't reload if we already have the map
	if (zenarioO.map && !reload) {
		return;
	}
	
	zenarioA.showAJAXLoader();
	
	//if (window.console && typeof console.groupEnd == 'function') console.group('Loading Storekeeper');
	
	zenarioO.pageTitle = document.title;
	
	if (!zenario.browserIsIE(7)
	 && zenarioA.siteSettings.storekeeper_page_size
	 && zenarioA.siteSettings.storekeeper_page_size == 1*zenarioA.siteSettings.storekeeper_page_size) {
		zenarioO.pageSize = Math.min(500, 1*zenarioA.siteSettings.storekeeper_page_size);
	}
	
	//(Periodic refresh is now disabled)
	//if (zenarioA.siteSettings.storekeeper_periodic_refresh_mins
	// && zenarioA.siteSettings.storekeeper_periodic_refresh_mins == 1*zenarioA.siteSettings.storekeeper_periodic_refresh_mins) {
	//	zenarioO.periodicRefreshTime = 60000 * zenarioA.siteSettings.storekeeper_periodic_refresh_mins;
	//}
	
	
	if (window.zenarioONotFull) {
		zenarioO.showLeftColumn = false;
	} else {
		zenarioO.showLeftColumn = true;
	}
	
	//Set the search box to the default search Phrase
	zenarioO.setSearch(undefined);
	
	//Load the current admin's Storekeeper preferences
	zenarioO.checkPrefs();
	
	//Load the map
	try {
		//If this is a select mode window launched from storekeeper, try to copy the map off of the launching storekeeper
		if (	   zenarioA.openedInIframe
				&& self.parent
				&& self.parent.zenarioO
				&& self.parent.zenarioO.map
				&& self.parent.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& self.parent.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = self.parent.zenarioO.map;
			zenarioO.init2();
			return;
		
		//Check if this window had an opener, which already had Storekeeper loaded
		} else if (window.opener
				&& window.opener.zenarioO
				&& window.opener.zenarioO.map
				&& window.opener.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& window.opener.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = window.opener.zenarioO.map;
			zenarioO.init2();
			return;
		
		//Check if this window had an opener, which already had Storekeeper preloaded
		} else if (window.opener
				&& window.opener.get
				&& window.opener.get('zenario_sk_iframe')
				&& window.opener.get('zenario_sk_iframe').contentWindow
				&& window.opener.get('zenario_sk_iframe').contentWindow.zenarioO
				&& window.opener.get('zenario_sk_iframe').contentWindow.zenarioO.map
				&& window.opener.get('zenario_sk_iframe').contentWindow.zenarioA.moduleCodeHash === zenarioA.moduleCodeHash
				&& window.opener.get('zenario_sk_iframe').contentWindow.zenarioCSSJSVersionNumber === window.zenarioCSSJSVersionNumber
		) {
			zenarioO.map = window.opener.get('zenario_sk_iframe').contentWindow.zenarioO.map;
			zenarioO.init2();
			return;
		}
	} catch (e) {
		//Run the code below if something went wrong
	}
	
	
	var store, url = URLBasePath + 'zenario/admin/ajax.php?_json=1';
	
	//Attempt to get the map from the local store
	if (zenarioO.map = zenario.checkSessionStorage(url, {}, true)) {
		zenarioO.init2();
	
	} else {
		//Otherwise launch an AJAX request to get the map
		zenarioA.keepTrying(function(attempt) {
			$.ajax({
				url: url,
				dataType: 'text'
			}).fail(function(resp, statusType, statusText) {
				if (zenarioA.stopTrying(attempt)) {
					zenarioA.AJAXErrorHandler(resp, statusType, statusText);
				}
				
			}).done(function(data) {
				if (zenarioA.stopTrying(attempt)) {
					if (zenarioO.map = zenarioA.readData(data, url, {})) {
						zenarioO.init2();
					}
				}
			
			});
		});
	}
};


zenarioO.init2 = function() {
	zenarioO.lookForBranches(zenarioO.map);
	
	zenarioA.tooltips();
	
	
	zenarioO.createSortArrayTopLevel('sortedTopLevelItems');
	zenarioO.setTopLevelItems();
	
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

//Update the current admin's Storekeeper preferences from the server to the client if needed.
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

//Save the current admin's Storekeeper preferences from the client to the server.
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




//Go to a series of locations in turn
zenarioO.checkQueue = function(sameLoad) {
	var len;
	if (len = zenarioO.checkQueueLength()) {
		var ids = '',
			queued = window.zenarioOQueue.shift();
		
		if (queued.selectedItems) {
			ids = zenarioO.implodeKeys(queued.selectedItems);
			
			zenarioO.selectedItems = queued.selectedItems;
			zenarioO.saveSelection(true);
		}
			
		if (queued.refinerName) {
			queued.refiner = {name: queued.refinerName, id: ids};
		}
		
		zenarioO.go(queued.path, queued.branch, queued.refiner, true, !--len, undefined, undefined, undefined, sameLoad);
		
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

//If a Plugin Nest opened this Storekeeper Window, refresh that nest
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
		
			if (window.opener && window.opener.zenario) {
				window.opener.document.location.hash = hash;
		
			} else if (window.parent && window.parent.zenario) {
				window.parent.document.location.hash = hash;
			}
		}
	
		if (window.zenarioOReloadOnChanges == 'zenarioO'
		 && window.parent
		 && window.parent.zenarioO
		 && window.parent.zenarioO.init) {
			window.parent.zenarioO.refresh();
		}
	}
};



zenarioO.pathNotAllowed = function(link) {
	return link && window.zenarioONotFull && window.zenarioOMaxPath !== false && zenarioO.path == window.zenarioOMaxPath && link.path != zenarioO.path;
};


//Go to a location
zenarioO.goNum = 0;
zenarioO.loadNum = 0;
zenarioO.lastSuccessfulGoNum = 0;
zenarioO.go = function(path, branch, refiner, queued, lastInQueue, backwards, dontUseCache, itemToSelect, sameLoad, runFunctionAfter, periodic) {
	
	if (!periodic) {
		zenarioO.lastActivity = Date.now();
	}
	
	if (!zenarioA.storekeeperWindow && !zenarioA.checkIfBoxIsOpen('sk' + skNum)) {
		return;
	}
	
	if (zenarioA.uploading) {
		return false;
	}
	
	//If the map has not yet loaded, wait until it has!
	if (!zenarioO.map) {
		if (!window.zenarioONotFull) {
			window.zenarioOQueue = [{path: path, branch: -1}];
		}
		return;
	}
	
	if (!queued) {
		window.zenarioOQueue = false;
	}
	
	var itemToSelect, inCloseUpView, specificItemRequested, specificItemsRequested, selectAnItem,
		requestedPath = path;
	
	if (path) {
		var pathAndItem = path.split('//'),
			len = pathAndItem.length;
		
		//Is there any deeplinking in the path?
		if (len > 2) {
			itemToSelect = undefined;
			var lastLink;
			for (var p = 0; p < len-1; p += 2) {
				
				//Navigate to the start of the branch
				var link;
				if (p == 0) {
					link = zenarioO.getFromToFromLink(pathAndItem[0]);
					window.zenarioOQueue = [{path: link.from, branch: -1}];
				
				} else {
					var combinedPath;
					
					if (pathAndItem[p].substr(0, 1) == '/') {
						combinedPath = pathAndItem[p].substr(1);
					} else {
						combinedPath = lastLink.to + '/' + pathAndItem[p];
					}
					
					link = zenarioO.getFromToFromLink(combinedPath);
					
					if (lastLink.to != link.from) {
						window.zenarioOQueue.push({path: link.from});
					}
				}
				
				//Select any items 
				var selectedItems = {};
				if (pathAndItem[p+1]) {
					var items = pathAndItem[p+1].split(',');
					foreach (items as var i) {
						selectedItems[items[i]] = true;
					}
				}
				
				//Look for any refiners
				var refinerName = false;
				if (link.refiner) {
					refinerName = link.refiner;
				}
				
				var linkTo = link.to;
				
				if (!linkTo) {
					//add some debug information here
					zenarioA.showMessage('The requested path "' + requestedPath + '" was not found in the system. If you have just updated or added files to the CMS, you will need to reload the page.', undefined, 'error', false, true);
					return false;				
				}
				
				//Select an item on the last panel, if asked for
				if (p+2 == len-1) {
					linkTo += '//' + pathAndItem[p+2];
				}
				
				//Do the branch
				window.zenarioOQueue.push({path: linkTo, branch: true, selectedItems: selectedItems, refinerName: refinerName});
				
				lastLink = link;
			}
			
			zenarioO.checkQueue(true);
			return;
		
		//Is an item requested in the path?
		} else if (len == 2) {
			itemToSelect = undefined;
			path = pathAndItem[0];
			
			if (pathAndItem[1].indexOf(',') != -1) {
				specificItemsRequested = pathAndItem[1];
				inCloseUpView = false;
				selectAnItem = false;
				
			} else if (itemToSelect = pathAndItem[1]) {
				var len = itemToSelect.length - 1;
				if (itemToSelect.substr(len) == '/') {
					itemToSelect = itemToSelect.substr(0, len);
					selectAnItem = false;
				} else {
					selectAnItem = true;
				}
				
				specificItemRequested = true;
				inCloseUpView = true;
			} else {
				inCloseUpView = false;
				selectAnItem = false;
			}
		
		//Check the itemToSelect input, and select that item after the jump if possible
		} else {
			inCloseUpView = false;
			
			if (itemToSelect) {
				specificItemRequested = true;
				selectAnItem = true;
			} else {
				selectAnItem = false;
			}
		}
		
		path = zenarioO.shortenPath(path);
	}
	
	//If a path has not been set, go to the initial panel
	if (!path) {
		path = window.zenarioONotFull? zenarioO.defaultPathInIframePreload : zenarioO.defaultPath;
		inCloseUpView = false;
		selectAnItem = false;
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
	
	
	var filtersSet = false,
		filters = $.extend(true, {}, zenarioO.loadFromBranches(path, branch, 'filters')),
		searchTerm = zenarioO.loadFromBranches(path, branch, 'searches'),
		advancedSearch = zenarioO.loadFromBranches(path, branch, 'adv_searches'),
		openItemsInHierarchy = zenarioO.loadFromBranches(path, branch, 'open_items'),
		advancedValues = false,
		refinerDetails = false,
		previousRefiner = false,
		viewOptionsPath = path;
	
	//Check to see if a refiner is set
	if (refiner === undefined) {
		refiner = zenarioO.loadRefiner(path, branch);
	}
	
	//If this panel requires a refiner but none has been set, don't attempt to show it and go to the panel above instead.
	if (!refiner && engToBoolean(zenarioO.followPathOnMap(path, 'refiner_required'))) {
		zenarioA.showMessage('A refiner was required, but none was set.', true, error);
		return;
	}
	
	//Attempt to find the refiner used on the panel before this one, if there was one
	var lastRefiners = {},
		branches = zenarioO.branches.length - 1,
		b;
	
	//If we're resetting all branches, there will be no previous refiner
	if (branch !== -1) {
		
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
	
	//If there is a refiner, try to look it up on the map to see if there is any special logic for it
	if (refiner && (refinerDetails = zenarioO.followPathOnMap(path + '/refiners/' + refiner.name))) {
		//Differ the view options on this panel depending on the value of a previous refiner
		if ((previousRefiner = refinerDetails.use_different_view_options_per_id_in_previous_refiner)
		 && (lastRefiners['refiner__' + previousRefiner] !== undefined)) {
			viewOptionsPath = path + '/refiners/' + previousRefiner + '/' + lastRefiners['refiner__' + previousRefiner];
		
		//Differ the view options on this panel depending on the value of the current refiner
		} else if (engToBoolean(refinerDetails.use_different_view_options_per_refiner_id)) {
			viewOptionsPath = path + '/refiners/' + refiner.name + '/' + refiner.id;
		
		//Differ the view options on this panel for the current refiner
		} else if (engToBoolean(refinerDetails.use_different_view_options)) {
			viewOptionsPath = path + '/refiners/' + refiner.name;
		}
	}
	
	if (advancedSearch
	 && zenarioO.prefs[viewOptionsPath]
	 && zenarioO.prefs[viewOptionsPath].adv_searches
	 && zenarioO.prefs[viewOptionsPath].adv_searches[advancedSearch]) {
		advancedSearchValues = zenarioO.prefs[viewOptionsPath].adv_searches[advancedSearch];
	} else {
		advancedSearch = false;
	}
	
	
	var defaultSortColumn = zenarioO.followPathOnMap(path, 'default_sort_column');
	if (!defaultSortColumn) {
		defaultSortColumn = 'name';
	}
	
	//Check if we need to check with the server for updates/changes to this panel using php
	var db_items = zenarioO.followPathOnMap(path, 'db_items'),
		reorder = zenarioO.followPathOnMap(path, 'reorder'),
		server_side = db_items && !engToBoolean(db_items.client_side),
		thisPageSize = reorder && reorder.column? 999 : zenarioO.pageSize,
		url = URLBasePath + 'zenario/admin/ajax.php?_json=1&path=' + path,
		requests = {},
		post = {},
		postUsed = false,
		data = false,
		store,
		go2 = function(data) {
			zenarioO.go2(path, url, requests, viewOptionsPath, branch, goNum, defaultSortColumn, thisPageSize, specificItemRequested, inCloseUpView, itemToSelect, selectAnItem, searchTerm, advancedSearch, filtersSet, refiner, lastRefiners, server_side, backwards, runFunctionAfter, data);
		};
	
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
	
	if (server_side || zenarioO.CSVExport) {
		requests._limit = thisPageSize;
		
		if (zenarioO.refreshToPage) {
			requests._start = (zenarioO.refreshToPage-1) * thisPageSize;
		} else {
			requests._start = 0;
			
			if (specificItemsRequested) {
				requests._item = specificItemsRequested;
			
			} else if (specificItemRequested && itemToSelect) {
				requests._item = itemToSelect;
			
			} else if (specificItemRequested) {
				requests._item = specificItemRequested;
			
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
		} else if (zenarioO.prefs[viewOptionsPath] && zenarioO.prefs[viewOptionsPath].sortBy) {
			requests._sort_col = zenarioO.prefs[viewOptionsPath].sortBy;
			requests._sort_desc = zenarioO.prefs[viewOptionsPath].sortDesc? 1 : 0;
		
		//Otherwise sort by the default sort column
		} else {
			requests._sort_col = defaultSortColumn;
			requests._sort_desc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
		}
	}
	//Send the values of any filters
	if (filters !== undefined) {
		//Don't apply hidden filters, or any empty filters
		foreach (filters as var c) {
			if (zenarioO.filterSetOnColumn(c, filters)) {
				filtersSet = true;
			} else {
				delete filters[c];
			}
		}
		
		requests._filters = JSON.stringify(filters);
	}
	
	if (server_side || zenarioO.CSVExport) {
		if (searchTerm !== undefined) {
			requests._search = searchTerm;
		}
		if (advancedSearch) {
			postUsed = true;
			post._adv_search = JSON.stringify(advancedSearchValues);
		}
	}
	
	if (reorder && reorder.lazy_load) {
		if (itemToSelect && !openItemsInHierarchy) {
			requests._openItemInHierarchy = itemToSelect;
		} else {
			requests._openItemsInHierarchy = openItemsInHierarchy? zenarioO.implodeKeys(openItemsInHierarchy) : '';
		}
	}
	
	//CSV export ability:
	//Rather than send data to the server and display it in the client,
	//send data to the server and download it as a CSV file.
	if (zenarioO.CSVExport) {
		zenarioO.CSVExport = undefined;
		zenarioO.refreshToPage = undefined;
		
		delete requests._start;
		delete requests._limit;
		
		if (path === zenarioO.path) {
			post._shownColumnsInCSV = zenarioO.getShownColumns(viewOptionsPath, defaultSortColumn, zenarioO.focus.columns);
		} else {
			post._shownColumnsInCSV = zenarioO.getShownColumns(viewOptionsPath, defaultSortColumn, zenarioO.followPathOnMap(path, 'columns'));
		}
		
		if (zenarioO.prefs[zenarioO.voPath] && zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV) {
			foreach (zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV as var c) {
				post._shownColumnsInCSV[c] = zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV[c];
			}
		}
		
		post._shownColumnsInCSV = JSON.stringify(post._shownColumnsInCSV);
		post._sortedColumns = JSON.stringify(ifNull(zenarioO.prefs[zenarioO.voPath].sortedColumns, zenarioO.sortedColumns));
		
		
		//We could just do something like:
		//	document.location.href = url + zenario.urlRequest(requests) + '&_csv=1';
		//to download the CSV file, but the URL would probably be too long.
		//So we'll fake a form submission as a work-around instead.
		get('zenario_iframe_form').action = url + zenario.urlRequest(requests) + '&_csv=1';
		
		var html = '', request;
		foreach (post as request) {
			html += '<input type="hidden" name="' + htmlspecialchars(request) + '" value="' + htmlspecialchars(post[request]) + '"/>';
		}
		get('zenario_iframe_form').innerHTML = html;
		
		get('zenario_iframe_form').submit();
		return;
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
	
	//Attempt to get this panel from the local storage, unless there's a post request
	if (postUsed) {
		//Don't use the local storage for post requests
		zenarioA.keepTrying(function(attempt) {
			$.ajax({
				url: url + zenario.urlRequest(requests),
				data: post,
				type: 'POST',
				dataType: 'text'
			
			}).fail(function(resp, statusType, statusText) {
				if (zenarioA.stopTrying(attempt)) {
					zenarioA.AJAXErrorHandler(resp, statusType, statusText);
				}
			
			}).done(function(data) {
				if (zenarioA.stopTrying(attempt)) {
					if (data = zenarioA.readData(data)) {
						go2(data);
					}
				}
			});
		});
	
	} else if (db_items && !dontUseCache && (store = zenario.checkSessionStorage(url, requests, true, zenarioO.loadNum))) {
		//Use the value from the local storage if possible...
		go2(store);
	
	} else {
		//...otherwise do an AJAX request then save the results into the local storage
		zenarioA.keepTrying(function(attempt) {
			$.ajax({
				url: url,
				data: requests,
				dataType: 'text'
			
			}).fail(function(resp, statusType, statusText) {
				if (zenarioA.stopTrying(attempt)) {
					zenarioA.AJAXErrorHandler(resp, statusType, statusText);
				}
			
			}).done(function(data) {
				if (zenarioA.stopTrying(attempt)) {
					if (data = zenarioA.readData(data, url, requests)) {
						go2(data);
					}
				}
			});
		});
	}
};



//Tidy these long params up into an object..?

//Part 2 of the go function, run when we've got the data back from the server
	//(Or run straight away if the server was never polled)
zenarioO.go2 = function(path, url, requests, viewOptionsPath, branch, goNum, defaultSortColumn, thisPageSize, specificItemRequested, inCloseUpView, itemToSelect, selectAnItem, searchTerm, advancedSearch, filtersSet, refiner, lastRefiners, server_side, backwards, runFunctionAfter, data) {
	
	//For debugging
	if (data && data.comment !== undefined) {
		console.log(data.comment);
	}
	
	if (!zenarioA.storekeeperWindow && !zenarioA.checkIfBoxIsOpen('sk' + skNum)) {
		return;
	}
	
	//Check that this isn't an out-of-date request that has come in syncronously via AJAX
	if (goNum != zenarioO.goNum) {
		return;
	}
	
	zenarioO.stopRefreshing();
	
	//If this is a different panel, scroll back up to the top
	if (zenarioO.voPath != viewOptionsPath || branch === true || branch === -1) {
		zenarioO.scrollToPageTop = true;
	}
	
	var lastPath = zenarioO.path;
	var lastTitle, noReturnEnabled;
	if (zenarioO.focus) {
		lastTitle = zenarioO.focus.title;
		noReturnEnabled = engToBoolean(zenarioO.focus.no_return);
	}
	
	zenarioO.path = path;
	zenarioO.url = url + zenario.urlRequest(requests);
	zenarioO.voPath = viewOptionsPath;
	zenarioO.defaultSortColumn = defaultSortColumn;
	zenarioO.thisPageSize = thisPageSize;
	zenarioO.filtersSet = filtersSet;
	zenarioO.server_side = server_side;
	zenarioO.inCloseUpView = inCloseUpView;
	zenarioO.itemToSelect = itemToSelect;
	zenarioO.selectAnItem = selectAnItem;
	zenarioO.advancedSearch = advancedSearch;
	
	zenarioO.markIfViewIsFiltered();
	
	if (!zenarioO.prefs[viewOptionsPath]) {
		zenarioO.prefs[viewOptionsPath] = {};
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
	
	zenarioO.saveRefiner(refiner);
	zenarioO.lastRefiners = lastRefiners;
	
	//ids -> parent ids
	zenarioO.itemHierarchy = {};
	
	//Focus on this part of the map
	delete zenarioO.focus;
	zenarioO.focus = data;
	
	if (zenarioO.focus.__item_hierarchy__) {
		zenarioO.itemHierarchy = zenarioO.focus.__item_hierarchy__;
	}
	
	
	//Set the format for labels
	if (!(zenarioO.labelFormat = zenarioO.focus.label_format_for_grid_view)) {
		zenarioO.labelFormat = '[[' + zenarioO.defaultSortColumn + ']]';
	}
	zenarioO.popoutLabelFormat = zenarioO.focus.label_format_for_popouts;
	
	//Check if we have a column description, and set a default one if not
	if (!zenarioO.focus.columns) {
		zenarioO.focus.columns = {'name': {'title': 'Name'}};
	}
	
	
	//Handle preferences
	//Sort by the reorder column if we're reordering
	var defaultSortColumn, reorder = zenarioO.followPathOnMap(path, 'reorder');
	if (reorder && reorder.column) {
		zenarioO.sortBy = reorder.column;
		zenarioO.sortDesc = false;
	
	//Look up the user's choice of search column
	} else if (zenarioO.prefs[viewOptionsPath].sortBy && zenarioO.focus.columns[zenarioO.prefs[viewOptionsPath].sortBy]) {
		zenarioO.sortBy = zenarioO.prefs[viewOptionsPath].sortBy;
		zenarioO.sortDesc = zenarioO.prefs[viewOptionsPath].sortDesc;
	
	//Sort by the default sort column if this is defined
	} else if (defaultSortColumn = zenarioO.followPathOnMap(path, 'default_sort_column')) {
		zenarioO.sortBy = defaultSortColumn;
		zenarioO.sortDesc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
	
	//Otherwise sort by name by default
	} else {
		zenarioO.sortBy = zenarioO.defaultSortColumn;
		zenarioO.sortDesc = engToBoolean(zenarioO.followPathOnMap(path, 'default_sort_desc'));
	}
	
	//If hierarchy view is being used on a panel, that will disable grid and list view
	if (zenarioO.focus.__item_hierarchy__) {
		zenarioO.view_mode = 'hierarchy';
		
	} else {
		//Look for a user's preference for view mode, otherwise look for a default view mode, otherwise default to grid view
		if (zenarioO.focus.force_view_mode) {
			zenarioO.view_mode = zenarioO.focus.force_view_mode;
		
		} else if (zenarioO.prefs[viewOptionsPath].view_mode) {
			zenarioO.view_mode = zenarioO.prefs[viewOptionsPath].view_mode;
		
		} else if (zenarioO.focus.view_mode) {
			zenarioO.view_mode = zenarioO.focus.view_mode;
		
		} else {
			zenarioO.view_mode = 'grid';
		}
		
		//Check that view_mode is a valid choice
		if (zenarioO.view_mode !== 'grid' && zenarioO.view_mode !== 'list') {
			zenarioO.view_mode = 'grid';
		}
	}
	
	zenarioO.createSortArray('sortedColumns', 'columns', 'ord');
	zenarioO.createSortArray('sortedItemButtons', 'item_buttons', 'ord');
	zenarioO.createSortArray('sortedInlineButtons', 'inline_buttons', 'ord');
	zenarioO.createSortArray('sortedCollectionButtons', 'collection_buttons', 'ord');
	zenarioO.createSortArray('sortedQuickFilterButtons', 'quick_filter_buttons', 'ord');
	
	//Work out which columns should be shown
	zenarioO.shownColumns = zenarioO.getShownColumns(viewOptionsPath, defaultSortColumn, zenarioO.focus.columns);
	
	//Check if the Admin has a customised sort order on this Panel
	if (zenarioO.prefs[viewOptionsPath].sortedColumns) {
		//Get an array-flip of their sorted columns, so we can look up each column's order
		var existingSortedColumns = {};
		foreach (zenarioO.prefs[viewOptionsPath].sortedColumns as var colNo) {
			var c = zenarioO.prefs[viewOptionsPath].sortedColumns[colNo];
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
					zenarioO.prefs[viewOptionsPath].sortedColumns.splice(colPos, 0, c);
					
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
		
		zenarioO.sortedColumns = zenarioO.prefs[viewOptionsPath].sortedColumns;
	}
	
	
	
	if (zenarioO.focus.items && zenarioO.focus.items.count == 1 && engToBoolean(zenarioO.focus.allow_bypass)) {
		zenarioO.branches[zenarioO.branches.length-1].bypasses[zenarioO.path] = true;
	} else {
		delete zenarioO.branches[zenarioO.branches.length-1].bypasses[zenarioO.path];
	}
	
	
	//Check the queue to see if there are any more navigation commands queued after this destination. If so, run them, and stop processing this one
	if (zenarioO.checkQueue(true)) {
		return;
	}
	
	//If there is only one item and "allow_bypass" is enabled, skip right past this panel
	if (zenarioO.focus.items && zenarioO.focus.items.count == 1 && engToBoolean(zenarioO.focus.allow_bypass)) {
		if (backwards) {
			if (zenarioO.getBackButtonTitle() !== false) {
				zenarioO.back();
				return;
			}
		} else {
			var link;
			if (zenarioO.focus.items) {
				foreach (zenarioO.focus.items as var id) {
					if (!zenarioO.isInfoTag(id)) {
						zenarioO.selectedItems = {};
						zenarioO.selectedItems[id] = true;
						zenarioO.saveSelection();
						
						if (zenarioO.itemDoubleClickAction(id)) {
							return;
						} else {
							break;
						}
					}
				}
			}
		}
	
	//If there are no items in this Panel and the return_if_empty flag is set, attempt to go back
	} else if ((!zenarioO.focus.items || !zenarioO.focus.items.count) && engToBoolean(zenarioO.focus.return_if_empty)) {
		if (zenarioO.getBackButtonTitle() !== false) {
			zenarioO.back();
			return;
		}
	}
	
	
	//Look to see if there are any item_link type columns
	var menuIds = {},
		contentTags = {},
		cTypes = {},
		value;
	
	foreach (zenarioO.focus.columns as var c) {
		if (zenarioO.isShowableColumn(c)) {
			if (zenarioO.focus.columns[c].item_link == 'menu_item') {
				
				foreach (zenarioO.focus.items as var i) {
					if (!zenarioO.isInfoTag(i)
					 && (value = zenarioO.focus.items[i][c])
					 && (value == 1*value)) {
						var lang = zenarioO.itemLanguage(i);
						
						if (!menuIds[lang]) {
							menuIds[lang] = '';
						} else {
							menuIds[lang] += ',';
						}
						
						menuIds[lang] += value;
					}
				}
				
			} else if (zenarioO.focus.columns[c].item_link == 'content_item' || zenarioO.focus.columns[c].item_link == 'content_item_or_url') {
				
				foreach (zenarioO.focus.items as var i) {
					if (!zenarioO.isInfoTag(i)
					 && (value = zenarioO.focus.items[i][c])) {
						var cTypeAndId = value.split('_');
						if (cTypeAndId[0]
						 && cTypeAndId[0] != 'null'
						 && cTypeAndId[0] === cTypeAndId[0].replace(/\W/, '')
						 && cTypeAndId[1]
						 && cTypeAndId[1] == 1*cTypeAndId[1]) {
							var lang = zenarioO.itemLanguage(i),
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
			}
		}
	}
	
	
	//Launch AJAX requests for each of the item links
	//As there may be more than one we'll launch them syncronously to save time
	//When all the requests have been completed we'll then proceed to the go3 function.
	zenarioO.menuItems = {};
	zenarioO.contentItems = {};
	zenarioO.itemLinkRequestsLeft = 0;
	zenarioO.shallowLinks = {'content_item': 'zenario__content/nav/content/panel', 'content_item_or_url': 'zenario__content/nav/content/panel', 'menu_item': 'zenario__menu/hidden_nav/menu_nodes/panel'};
	
	foreach (contentTags as var lang) {
		foreach (contentTags as var parent) {
			if (contentTags[lang][parent]) {
			
				if (!zenarioO.contentItems[lang]) {
					zenarioO.contentItems[lang] = {}
				}
				if (!zenarioO.contentItems[lang][parent]) {
					zenarioO.contentItems[lang][parent] = {}
				}
			
				var url =
					URLBasePath +
					'zenario/admin/ajax.php?_json=1&path=' + zenarioO.shallowLinks['content_item'] +
					'&_get_item_links=' + contentTags[lang][parent] +
					'&languageId=' + encodeURIComponent(lang);
				
				if (!(zenarioO.contentItems[lang][parent] = zenario.checkSessionStorage(url, {}, true, zenarioO.loadNum))) {
					++zenarioO.itemLinkRequestsLeft;
					zenarioO.getDataHack(url, lang, function(url, lang, data) {
						if (goNum == zenarioO.goNum) {
							zenarioO.contentItems[lang][parent] = zenarioA.readData(data, url, {});
							if (!--zenarioO.itemLinkRequestsLeft) {
								zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
							}
						}
					});
				}
			}
		}
	}
	
	foreach (menuIds as var lang) {
		if (menuIds[lang]) {
			
			if (!zenarioO.menuItems[lang]) {
				zenarioO.menuItems[lang] = {}
			}
			
			var url =
				URLBasePath +
				'zenario/admin/ajax.php?_json=1&path=' + zenarioO.shallowLinks['menu_item'] +
				'&_get_item_links=' + menuIds[lang] +
				'&languageId=' + encodeURIComponent(lang) +
				'&refinerName=language' +
				'&refinerId=' + encodeURIComponent(lang) +
				'&refiner__language=' + encodeURIComponent(lang);
			
			if (!(zenarioO.menuItems[lang] = zenario.checkSessionStorage(url, {}, true, zenarioO.loadNum))) {
				++zenarioO.itemLinkRequestsLeft;
				zenarioO.getDataHack(url, lang, function(url, lang, data) {
					if (goNum == zenarioO.goNum) {
						zenarioO.menuItems[lang] = zenarioA.readData(data, url, {});
						if (!--zenarioO.itemLinkRequestsLeft) {
							zenarioO.go3(goNum, searchTerm, backwards, runFunctionAfter);
						}
					}
				});
			}
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
	setTimeout(
		function() {
			//Hack to try and prevent a possible bug where the GET request gets too large:
				//Convert it to a post request if it gets too big
			var post = url.length > 2000,
				data = zenario.nonAsyncAJAX(url, post);
			success(url, lang, data);
		}, 1);
};


zenarioO.go3 = function(goNum, searchTerm, backwards, runFunctionAfter) {
	
	if (!zenarioA.storekeeperWindow && !zenarioA.checkIfBoxIsOpen('sk' + skNum)) {
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
	
	zenarioO.setTopLevelItems();
	
	//Display a pop-up message, if this is not a refresh and one has been requested
	if (!backwards && zenarioO.focus.popout_message) {
		if (zenarioO.refreshToPage === undefined || zenarioO.focus.popout_message != zenarioO.lastPopoutMessage) {
			zenarioA.showMessage(zenarioO.focus.popout_message, true, false);
		}
		
		zenarioO.lastPopoutMessage = zenarioO.focus.popout_message;
	}
	
	//Show/hide the search box, and set the search term
		//(Don't do this if this was a page refresh, not a page jump)
	if (zenarioO.refreshToPage === undefined) {
		zenarioO.setSearch(searchTerm);
	}
	
	zenarioO.searchAndSortItems();
	
	//(Periodic refresh is now disabled)
	//if (!engToBoolean(zenarioO.focus.disable_periodic_refresh)) {
	//	zenarioO.periodicRefresh();
	//}
	
	if (!zenarioO.firstLoaded) {
		zenarioO.firstLoaded = true;
		zenarioA.hideAJAXLoader();
	}
	
	zenarioO.setWrapperClass('loaded', true);
	
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
	
	if (i && zenarioO.focus && zenarioO.focus.items && zenarioO.focus.items[i]
	 && (!zenarioO.focus.items[i].css_class || ('' + zenarioO.focus.items[i].css_class).indexOf('ghost') == -1)
	 && zenarioO.focus.items[i].language_id) {
		return zenarioO.focus.items[i].language_id;
	
	} else if (zenarioO.focus && zenarioO.focus.key) {
		return ifNull(zenarioO.focus.key.languageId, zenarioO.focus.key.language);
	
	} else {
		return '';
	}
};


zenarioO.itemParent = function(i) {
	
	if (i && zenarioO.focus && zenarioO.focus.items && zenarioO.focus.items[i]
	 && zenarioO.focus.db_items && zenarioO.focus.db_items.hierarchy_column) {
	
		return zenarioO.focus.items[i][zenarioO.focus.db_items.hierarchy_column] || '';
	
	} else {
		return '';
	}
};


//Create a list of which columns are to be shown.
zenarioO.getShownColumns = function(viewOptionsPath, defaultSortColumn, columns) {	
	var firstColumn = false;
	var columnsShown = false;
	var shownColumns = {};
	
	if (zenarioO.prefs && zenarioO.prefs[viewOptionsPath] && zenarioO.prefs[viewOptionsPath].shownColumns) {
		shownColumns = zenarioO.prefs[viewOptionsPath].shownColumns;
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


zenarioO.scrollToPageTop = false;
zenarioO.nextPage = function() {
	
	if (zenarioO.lockPageClicks) {
		return false;
	}
	
	if (zenarioO.page < zenarioO.pageCount) {
		zenarioO.scrollToPageTop = true;
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
		zenarioO.scrollToPageTop = true;
		zenarioO.goToPage(zenarioO.page - 1);
	}
};

zenarioO.goToPage = function(page) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
		return false;
	}
	
	zenarioO.lockPageClicks = true;
	zenarioO.inCloseUpView = false;
	
	if (zenarioO.server_side) {
		zenarioO.refreshAndShowPage(page);
	} else {
		zenarioO.showPage(page);
	}
};

zenarioO.refreshAndShowPage = function(page) {
	zenarioO.page = ifNull(page, 1);
	zenarioO.refresh(false, true);
};

zenarioO.showPage = function(page) {
	zenarioO.page = page;
	zenarioO.drawPanel();
};

zenarioO.searchAndSortItems = function() {
	var id,
		matches,
		numeric,
		searchUnused = zenarioO.searchTerm === undefined || get('organizer_search').style.visibility == 'hidden';
	zenarioO.nonSearchMatches = {};
	
	if (zenarioO.server_side) {
		//If server side sorting and searching is being used, we can use the sort order from the server as
		//our searched and sorted list.
		//(Note that it's not possible to sort on the server but search on the client.)
		zenarioO.searchedItems = zenarioO.focus.__item_sort_order__;
		zenarioO.searchMatches = zenarioO.focus.items.count;
	
	} else {
		//Otherwise we need to sort through the items on the client
		zenarioO.createSortArray('sortedItems', 'items', zenarioO.sortBy, zenarioO.sortDesc);
		
		//If they've already been searched on the server, or if there is no searching to do, we can stop here
		if (zenarioO.server_side || zenarioO.searchTerm === undefined || get('organizer_search').style.visibility == 'hidden') {
			zenarioO.searchedItems = zenarioO.sortedItems;
			zenarioO.searchMatches = zenarioO.focus.items? zenarioO.focus.items.count : undefined;
		
		//Otherwise, search on the client
		} else {
			//Firstly, work out which items are matched by the search term
			zenarioO.searchedItems = {};
			zenarioO.searchMatches = 0;
			var matchedItemsArray = [],
				matchedItemsObject = {},
				lowerCaseSearchTerm = zenarioO.searchTerm.toLowerCase(),
				itemNo;
			
			foreach (zenarioO.sortedItems as itemNo) {
				id = zenarioO.sortedItems[itemNo];
				
				matches = false;
				foreach (zenarioO.focus.columns as var c) {
					if (!zenarioO.isInfoTag(c) && engToBoolean(zenarioO.focus.columns[c].searchable)) {
						value = zenarioO.columnValue(id, c, true);
						
						if (value == (numeric = 1*value)) {
							matches = numeric == zenarioO.searchTerm;
						} else if (value) {
							matches = value.toLowerCase().indexOf(lowerCaseSearchTerm) !== -1;
						}
						
						if (matches) {
							matchedItemsArray.push(id);
							matchedItemsObject[id] = true;
							break;
						}
					}
				}
			}
			
			//In hierarchy mode, we should also add the parent nodes of any matched items
			if (zenarioO.focus.__item_hierarchy__) {
				
				for (itemNo = 0; itemNo < matchedItemsArray.length; ++itemNo) {
					id = matchedItemsArray[itemNo];
					
					if (zenarioO.itemHierarchy[id] && !matchedItemsObject[zenarioO.itemHierarchy[id]]) {
						matchedItemsArray.push(zenarioO.itemHierarchy[id]);
						matchedItemsObject[zenarioO.itemHierarchy[id]] = true;
						zenarioO.nonSearchMatches[zenarioO.itemHierarchy[id]] = true;
					}
				}
			}
			
			//Populate the array of searched items from the array of sorted items
			foreach (zenarioO.sortedItems as itemNo) {
				id = zenarioO.sortedItems[itemNo];
				
				if (matchedItemsObject[id]) {
					zenarioO.searchedItems[zenarioO.searchMatches++] = zenarioO.sortedItems[itemNo];
				}
			}
		}
	}
	
	zenarioO.itemsOrder = {};
	foreach (zenarioO.searchedItems as var itemNo) {
		if (!zenarioO.isInfoTag(itemNo)) {
			id = zenarioO.searchedItems[itemNo];
			zenarioO.itemsOrder[id] = 1*itemNo;
		}
	}
	
	var ord, page = 1;
	
	//Work out the current page number for server-side searching and sorting
	if (zenarioO.server_side && zenarioO.server_side) {
		page = ifNull(zenarioO.focus.__page__, zenarioO.refreshToPage, 1);
	
	//Work out the current page number for client-side sorting
	} else {
		if (zenarioO.refreshToPage !== undefined) {
			page = zenarioO.refreshToPage;
		} else {
			//If we've not got a specific target, check if any items were selected here and use the first we find as a target
			if (!zenarioO.itemToSelect) {
				zenarioO.itemToSelect = zenarioO.loadSelection();
			}
			
			//If the item we want is in the results, make sure we show that page
			if (ord = zenarioO.itemsOrder[zenarioO.itemToSelect]) {
				page = Math.floor(ord / zenarioO.thisPageSize) + 1;
			}
		}
	}
	
	
	
	//In Hierarchy mode, build the hierarchical arrays in sorted order
	
	//An array of parent ids -> ids
	zenarioO.itemHierarchyArray = {0: []};
	
	if (zenarioO.focus.__item_hierarchy__) {
			
		zenarioO.itemHierarchy = zenarioO.focus.__item_hierarchy__;
		
		var id;
		foreach (zenarioO.searchedItems as var itemNo) {
			id = zenarioO.searchedItems[itemNo];
			
			if (zenarioO.itemHierarchy[id] !== undefined) {
				if (!zenarioO.itemHierarchyArray[zenarioO.itemHierarchy[id]]) {
					zenarioO.itemHierarchyArray[zenarioO.itemHierarchy[id]] = [];
				}
				zenarioO.itemHierarchyArray[zenarioO.itemHierarchy[id]].push(id);
			} else {
				zenarioO.itemHierarchyArray[0].push(id);
			}
		}
	}
	
	
	delete zenarioO.refreshToPage;
	zenarioO.showPage(page);
};

zenarioO.drawPanel = function() {
	
	var i,
		className = 'disable_grid_view disable_list_view',
		bespoke = false,
		$rightColumnContent = $('#organizer_rightColumnContent'),
		scrollTop = $rightColumnContent.scrollTop(),
		scrollLeft = $rightColumnContent.scrollLeft();
		
	
	zenarioO.itemButtonsExist = false;
	zenarioO.changingHierarchy = false;
	zenarioO.setWrapperClass('changing_hierarchy', zenarioO.changingHierarchy);
	
	$('#organizer_applyButton').unbind();
	
	//Ability to add custom HTML to the right-side panel, rather than the usual Storekeeper logic
	if (zenarioO.focus.html || zenarioO.focus.url) {
		bespoke = 'html';
	
	//Regular Storekeeper behaviour
	} else {
		className = zenarioO.view_mode + '_view';
		if (zenarioO.focus.__item_hierarchy__
		 || engToBoolean(zenarioO.focus.disable_grid_view)
		 || (zenarioO.focus.force_view_mode && zenarioO.focus.force_view_mode != 'grid')) {
			className += ' disable_grid_view';
		}
		if (zenarioO.focus.__item_hierarchy__
		 || engToBoolean(zenarioO.focus.disable_list_view)
		 || (zenarioO.focus.force_view_mode && zenarioO.focus.force_view_mode != 'list')) {
			className += ' disable_list_view';
		}
		
		if (zenarioO.reorderingUsingOldMethod) {
			//Stop reordering from running, if it was running
			$('#organizer_items_wrapper').sortable('destroy');
		}
		zenarioO.reordering = false;
		zenarioO.reorderingUsingOldMethod = false;
		zenarioO.reorderingUsingNewMethod = false;
		zenarioO.changingHierarchyView = false;
		
		if (zenarioO.focus.item_buttons) {
			foreach (zenarioO.focus.item_buttons as i) {
				if (!zenarioO.isInfoTag(i)) {
					zenarioO.itemButtonsExist = true;
					break;
				}
			}
		}
		
		
		zenarioO.loadSelection();
		
		if (window.zenarioOSelectMode) {
			zenarioO.multipleSelectEnabled = window.zenarioOMultipleSelect;
		} else {
			//Check to see if any item toolbar buttons allow for multiple selections, and enable multiple select if so
			zenarioO.multipleSelectEnabled = false;
			if (zenarioO.focus.item_buttons) {
				foreach (zenarioO.focus.item_buttons as i) {
					if (!zenarioO.isInfoTag(i)) {
						if (engToBoolean(zenarioO.focus.item_buttons[i].multiple_select)
						 && !zenarioO.checkButtonHidden(zenarioO.focus.item_buttons[i])) {
							zenarioO.multipleSelectEnabled = true;
							break;
						}
					}
				}
			}
		}
		
		
		if (zenarioO.inCloseUpView || zenarioO.selectAnItem) {
			if (zenarioO.itemsOrder[zenarioO.itemToSelect] !== undefined) {
				zenarioO.selectedItems = {};
				zenarioO.selectedItems[zenarioO.itemToSelect] = true;
				
				//Implement linking to a panel with an item selected using the same logic as linking to close-up view
				if (zenarioO.selectAnItem) {
					zenarioO.inCloseUpView = false;
				}
			} else {
				zenarioO.selectedItems = {};
				zenarioO.inCloseUpView = false;
			}
		
		}
		
		if (zenarioO.focus.reorder
		 && zenarioO.focus.reorder.hierarchy_view_mode
		 && !window.zenarioOSelectMode
		 && !zenarioO.filteredView) {
			zenarioO.changingHierarchyView = true;
		}
		
		if (zenarioO.focus.reorder
		 && zenarioO.focus.reorder.column
		 && !window.zenarioOSelectMode
		 && !zenarioO.filteredView) {
			
			if (zenarioO.view_mode == 'hierarchy') {
				if (zenarioO.focus.reorder.hierarchy_view_mode == 'parent_ids_and_ordinals') {
					zenarioO.reordering = true;
					zenarioO.reorderingUsingNewMethod = true;
				}
			
			} else {
				zenarioO.reordering = true;
				zenarioO.reorderingUsingOldMethod = true;
				className += ' reordering ' + zenarioO.view_mode + '_view_reordering';
			}
		}
		
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
	}
	
	
	//Show, hide, or highlight the advanced search button depending on whether it is enabled/in use
	//if (bespoke || !zenarioO.server_side || !engToBoolean(zenarioO.focus.advanced_search)) {
	//	$('#organizer_advancedSearch').addClass('advancedSearchDisabled').removeClass('advancedSearchEnabled').removeClass('advancedSearchOn');
	//
	//} else if (zenarioO.advancedSearch) {
	//	$('#organizer_advancedSearch').removeClass('advancedSearchDisabled').addClass('advancedSearchEnabled').addClass('advancedSearchOn');
	//	get('organizer_clearAdvancedSearchA').title = phrase.advancedSearchClear;
	//	zenarioA.tooltips('#organizer_clearAdvancedSearchA');
	//
	//} else {
	//	$('#organizer_advancedSearch').removeClass('advancedSearchDisabled').addClass('advancedSearchEnabled').removeClass('advancedSearchOn');
	//	get('organizer_clearAdvancedSearchA').title = phrase.advancedSearchNotOn;
	//	zenarioA.tooltips('#organizer_clearAdvancedSearchA');
	//}

	
	var scrollTo = zenarioO.itemToSelect;
	zenarioO.selectAnItem = false;
	zenarioO.lastClicked = false;
	zenarioO.CSVExportEnabled = false;
	
	if (bespoke) {
		zenarioO.drawBespokePanel();
		zenarioO.clearUpload();
	} else {
		zenarioO.drawRegularPanel();
		zenarioO.setPanelTitle();
		zenarioO.setPanelItems();
		zenarioO.setItemCount();
		
		zenarioO.setTrash();
		
		zenarioO.saveSelection();
		
		zenarioO.setPages();
		zenarioO.setButtons();
		zenarioO.setQuickFilters();
	
		
		var $organizer_rightColumnContent = $('#organizer_rightColumnContent'),
			$organizer_header = $('#organizer_rightColumnContent .organizer_header');
		
		if ($organizer_header.length) {
			$organizer_rightColumnContent.scroll(function() {
				$organizer_header.css('top', $organizer_rightColumnContent.scrollTop());
			});
		}
		
		get('organizer_viewModeSelected').className = className;
	}
	
	zenarioO.setBackButton();
	
	//Start reordering running, if it is enabled on this panel
	if (!bespoke && zenarioO.reorderingUsingOldMethod) {
		$('#organizer_items_wrapper').sortable({
			opacity: 0.8,
			stop: function (sorted) {
				//Handle the results of a reorder
				
				//Get a list of item ids in the new order
				var newOrder = $('#organizer_items_wrapper').sortable('toArray', {attribute: 'data-id'});
				var oldOrder = [];
				var values = [];
				var items = {};
				var saves = '';
				
				//Create an array of which items we can see
				foreach (newOrder as var itemNo) {
					var i = newOrder[itemNo];
					items[i] = true;
				}
				
				//Look through the searchedItems array and get their old order, and their value from the items array
				foreach (zenarioO.searchedItems as var itemNo) {
					var i = zenarioO.searchedItems[itemNo];
					if (items[i]) {
						oldOrder.push(i);
						values.push(zenarioO.focus.items[i][zenarioO.focus.reorder.column]);
					}
				}
				
				//Look through newOrder and oldOrder for any changes, and update those column
				var actionRequests = zenarioO.getKey();
				foreach (newOrder as var itemNo) {
					var i = newOrder[itemNo];
					if (i != oldOrder[itemNo]) {
						saves += (saves? ',' : '') + i;
						actionRequests['item__' + i] = values[itemNo];
					}
				}
				actionRequests.reorder = true;
				actionRequests.id = saves;
				
				//Send these results via AJAX
				var actionTarget =
					'zenario/ajax.php?' +
						'__pluginClassName__=' + zenarioO.focus.reorder.class_name +
						'&__path__=' + zenarioO.path +
						'&method_call=handleOrganizerPanelAJAX';
				
				//Clear the local storage, as there have probably just been changes
				delete zenario.rev;
				
				$.post(
					URLBasePath + actionTarget,
					actionRequests,
					//Refresh the panel to show the new order
					function () {
						zenarioO.refresh();
					}
				);
			}
		});
	}
	
	//Check that Storekeeper/the panel is the right size
	zenarioO.size(true);
	
	if (!bespoke) {
		$rightColumnContent = $('#organizer_rightColumnContent');
		
		if (zenarioO.scrollToPageTop || !scrollTop) {
			scrollTop = 0;
		}
		if (zenarioO.scrollToPageTop || !scrollLeft) {
			scrollLeft = 0;
		}
		
		$rightColumnContent.scrollTop(scrollTop);
		$rightColumnContent.scrollLeft(scrollLeft);
		
		//If there's an item specified to highlight, attempt to scroll to it
		if (!zenarioO.refreshIsPeriodic) {
		
			if (!scrollTo) {
				scrollTo = zenarioO.getKeyId(true);
			}
		
			if (scrollTo) {
				zenarioO.scrollToItem(scrollTo, 500);
			}
		}
	}
	
	zenarioO.lockPageClicks = false;
	zenarioO.scrollToPageTop = false;
	zenarioO.scrollToPageBottom = false;
	zenarioO.refreshIsPeriodic = false;
	
	//There seems to be a bug with setting the scrolling height on the latest version of chrome
	//Making and then removing a random change seems to fix this...
	if (zenario.browserIsChrome()) {
		setTimeout(function() {
			var $rightColumnContent = $('#organizer_rightColumnContent');
			
			$rightColumnContent.css('padding-right', '1px');
			setTimeout(function() {
				$rightColumnContent.css('padding-right', '0px');
			}, 1);
		}, 1);
	}
};


zenarioO.scrollToItem = function(scrollTo, time) {
	var offset, 
		$rightColumnContent = $('#organizer_rightColumnContent');
	
	if (offset = $(get('organizer_item_' + scrollTo)).position()) {
		
		offset = offset.top + $rightColumnContent.scrollTop();
		$rightColumnContent.stop().animate({ scrollTop: Math.max(0, offset - Math.floor($rightColumnContent.height() / 4)) }, time);
		
		return true;
	
	} else {
		return false;
	}
};

zenarioO.stripBodyPanelClasses = function() {
	$('body')
		.removeClass('zenario_organizer_bespoke_panel')
		.removeClass('zenario_organizer_bespoke_html_panel')
		.removeClass('zenario_organizer_bespoke_url_panel');
};

zenarioO.drawRegularPanel = function() {
	zenarioO.stripBodyPanelClasses();
	$('body').addClass('zenario_organizer_regular_panel');
	get('organizer_rightColumnInner').innerHTML = zenarioA.microTemplate('zenario_organizer_panel', {});
};
	
zenarioO.drawBespokePanel = function() {
	zenarioO.stripBodyPanelClasses();
	$('body').addClass('zenario_organizer_bespoke_panel');
	
	$('#organizer_quickFilter').hide().html('');
	
	if (zenarioO.focus.html) {
		$('body').addClass('zenario_organizer_bespoke_html_panel');
		get('organizer_rightColumnInner').innerHTML = zenarioO.focus.html;
	
	} else if (zenarioO.focus.url) {
		$('body').addClass('zenario_organizer_bespoke_url_panel');
		get('organizer_rightColumnInner').innerHTML = zenario.nonAsyncAJAX(zenario.addBasePath(zenarioO.focus.url));
	}
	
	zenarioO.setHash();
};



//Go backwards, if possible
zenarioO.back = function(times) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
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
	
	if (map._path_here) {
		panelPath = map._path_here;
	}
	
	foreach (map as var i) {
		if (map[i] && typeof map[i] == 'object') {
			
			var p = path? path + '/' + i : i;
			
			//Old logic: developers must manually specify which things are branches
			//var isBranch = map[i].link && typeof map[i].link == 'object' && map[i].link.branch;
			
			//New logic: all links are branches except for top/second-level items,
			//and top-level items may not contain links
			var isBranch = false;
			if (parentKey !== undefined && map[i].link && typeof map[i].link == 'object') {
				if (parentKey != 'nav') {
					map[i].link.branch = isBranch = true;
				
				} else {
					map[i].link.branch = isBranch = false;
					
					//Automatically turn links with refiners into deep links to that refiner
					if (map[i].link.refiner
					 && map[i].link.path
					 && map[i].link.path.indexOf('//') == -1) {
						map[i].link.path += '/refiners/' + map[i].link.refiner + '////';
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
		'bypasses': {},
		'filters': {},
		'refiners': {},
		'searches': {},
		'adv_searches': {},
		'selected_items': {},
		'open_items': {}
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
		'bypasses': {},
		'filters': {},
		'refiners': {},
		'searches': {},
		'adv_searches': {},
		'selected_items': {},
		'open_items': {}
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


zenarioO.getHash = function(ignoreSelectedItem, firstNBranches) {
	var hash = '',
		path = zenarioO.path,
		b, kb, lastTo, failed = false,
		oneItemSelected = false,
		selectedItem = '';
	
	if (!ignoreSelectedItem) {
		//Check if there was only one item selected
		foreach (zenarioO.selectedItems as var i) {
			if (zenarioO.selectedItems[i]) {
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
			if (!zenarioO.inCloseUpView) {
				selectedItem = oneItemSelected;
			} else {
				selectedItem = oneItemSelected + '/';
			}
		}
	}
	
	//Attempt to include the current branches taken in the URL by creating a deep link
	foreach (zenarioO.branches as var i) {
		if (i > 0 && ((firstNBranches === undefined) || (i <= firstNBranches))) {
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
				hash += kb + '//' + zenarioO.implodeKeys(zenarioO.branches[i-1].selected_items[b.from]);
				
				lastTo = b.to;
			} else {
				//If the branch wasn't registered then we can't create a deep link to it
				//Note that this should never happen, but if it does break I don't want Storekeeper breaking too
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
	zenarioO.filteredView = zenarioO.searchTerm !== undefined || zenarioO.filtersSet || zenarioO.advancedSearch;
};

zenarioO.doSearch = function(el) {
	var searchTerm;
	if (el.value === '') {
		searchTerm = undefined;
	} else {
		searchTerm = el.value;
		
		//Ensure that the searct term is numeric if possible
		if (searchTerm == 1*searchTerm) {
			searchTerm = 1*searchTerm;
		}
	}
	
	//Don't launch a search if the search term has not changed
	if (searchTerm === zenarioO.searchTerm) {
		return;
	}
	
	zenarioO.searchTerm = searchTerm;
	zenarioO.markIfViewIsFiltered();
	
	zenarioO.saveSearch();
	zenarioO.runSearch();
};

zenarioO.runSearch = function() {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
		return false;
	}
	
	zenarioO.lockPageClicks = true;
	zenarioO.inCloseUpView = false;
	
	if (zenarioO.server_side) {
		zenarioO.refreshAndShowPage();
	} else {
		zenarioO.searchAndSortItems();
	}
};

zenarioO.itemClicked = false;
zenarioO.lastClicked = false;
zenarioO.selectedItems = {};
zenarioO.openItemsInHierarchy = {};
zenarioO.itemClickTimeout = false;
zenarioO.itemClick = function(id, e, isCheckbox, useTimeOutIfDoubleClickEnabled) {
	if (zenarioO.itemClickTimeout) {
		clearTimeout(zenarioO.itemClickTimeout);
	}
	
	zenarioO.itemClicked = true;
	
	if (isCheckbox) {
		zenarioA.stop(e);
	}
	
	if (!zenarioO.focus || !zenarioO.focus.items[id]) {
		return false;
	}
	
	//if (zenarioO.lastClicked == id && zenario.browserIsMobile()) {
		//return zenarioO.itemDoubleClick(el);
	//}
	
	if (useTimeOutIfDoubleClickEnabled && zenarioO.itemDoubleClickLink(id)) {
		zenarioO.itemClickTimeout = setTimeout(function() {
			zenarioO.itemClick(id, e, isCheckbox);
		}, 300);
		
		return false;
	}
	
	//If the admin is holding down the shift key...
	if (zenarioO.multipleSelectEnabled && (e || event).shiftKey && zenarioO.lastClicked) {
		//...select everything between the current item and the last item that they clicked on
		zenarioO.selectItemRange(id, zenarioO.lastClicked);
	
	//If multiple select is enabled and the checkbox was clicked...
	} else if (zenarioO.multipleSelectEnabled && isCheckbox) {
		//...toogle the item that they've clicked on
		zenarioO.selectItem(id, !zenarioO.selectedItems[id]);
		zenarioO.toggleSelectAllCheckbox();
		zenarioO.lastClicked = id;
	
	//If multiple select is not enabled and the checkbox was clicked
	} else if (!zenarioO.multipleSelectEnabled && isCheckbox && zenarioO.selectedItems[id]) {
		//...deselect everything if this row was already selected
		zenarioO.deselectAllItems();
		zenarioO.toggleSelectAllCheckbox();
		zenarioO.lastClicked = id;
	
	//Otherwise select the item that they've just clicked on, and nothing else
	} else {
		zenarioO.selectSingleItem(id);
	}
	
	//Workaround for a bug where the checkbox can get out of sync when clicking directly on it
	if (isCheckbox) {
		setTimeout(function() {
			$(get('organizer_itemcheckbox_' + id)).prop('checked', !!zenarioO.selectedItems[id]);
		}, 1);
	}
	
	
	zenarioO.setButtons(true);
	zenarioO.saveSelection();
	
	return false;
};

//If the admin clicks on the background
zenarioO.itemBGClick = function() {
	zenarioO.itemClicked = false;
};

zenarioO.allItemsSelected = function() {
	if (zenarioO.shownItemsLength == 0) {
		return false;
	}
	
	var i, c = 0;
	foreach (zenarioO.selectedItems as i) {
		++c;
	}
	
	return c == zenarioO.shownItemsLength;
};

zenarioO.toggleSelectAll = function() {
	if (zenarioO.itemClickTimeout) {
		clearTimeout(zenarioO.itemClickTimeout);
	}
	
	if (zenarioO.allItemsSelected()) {
		zenarioO.deselectAllItems();
		zenarioO.setButtons(true);
		zenarioO.saveSelection();
	} else {
		zenarioO.selectAll();
	}
};

zenarioO.toggleSelectAllCheckbox = function(dontCloseSlideDownView) {
	if (zenarioO.allItemsSelected()) {
		$('#organizer_checkbox_col input').prop('checked', true);
	} else {
		$('#organizer_checkbox_col input').prop('checked', false);
	}
	
	if (!dontCloseSlideDownView) {
		zenarioO.closeSlidedownView();
	}
};

zenarioO.selectSingleItem = function(id, dontCloseSlideDownView) {
	zenarioO.deselectAllItems();
	zenarioO.selectItem(id, true);
	zenarioO.toggleSelectAllCheckbox(dontCloseSlideDownView);
	zenarioO.lastClicked = id;
};

zenarioO.selectItem = function(id, select) {
	var handle;
	if (select) {
		zenarioO.selectedItems[id] = true;
		$(get('organizer_itemcheckbox_' + id)).prop('checked', true);
		$(get('organizer_item_' + id)).addClass('organizer_selected');
	
	} else {
		delete zenarioO.selectedItems[id];
		$(get('organizer_itemcheckbox_' + id)).prop('checked', false);
		$(get('organizer_item_' + id)).removeClass('organizer_selected');
	}
};

zenarioO.selectItemRange = function(start, stop) {
	
	var select = start === undefined;
	
	foreach (zenarioO.searchedItems as var itemNo) {
		if (!zenarioO.isInfoTag(itemNo)) {
			var id = zenarioO.searchedItems[itemNo];
			
			if (zenarioO.shownItems && zenarioO.shownItems[id]) {
				zenarioO.selectItem(id, select || id === start || id === stop);
				
				if ((id === start && id === stop)) {
					select = false;
				} else if (id === start || id === stop) {
					select = !select;
				}
			}
		}
	}
	
	zenarioO.toggleSelectAllCheckbox();
};

zenarioO.selectAll = function() {
	zenarioO.selectItemRange();
	zenarioO.setButtons(true);
	zenarioO.saveSelection();
};

zenarioO.deselectAllItems = function() {
	zenarioO.selectItemRange(-1);
};

//Remember which items an admin had selected in the current panel
zenarioO.saveSelection = function(dontSetHash) {
	zenarioO.recordOpenItems();
	zenarioO.saveOpenItems();
	zenarioO.branches[zenarioO.branches.length-1].selected_items[zenarioO.path] = zenarioO.selectedItems;
	
	//Set the hash in the URL
	if (!dontSetHash && !zenarioO.checkQueueLength()) {
		zenarioO.setHash();
	}
};

zenarioO.saveOpenItems = function() {
	zenarioO.branches[zenarioO.branches.length-1].open_items[zenarioO.path] = zenarioO.openItemsInHierarchy;
};

zenarioO.recordOpenItems = function() {
	if (zenarioO.view_mode == 'hierarchy' && zenarioO.itemHierarchyArray) {
		zenarioO.openItemsInHierarchy = {};
		
		var id, parentId;
		
		foreach (zenarioO.itemHierarchyArray as parentId) {
			if (parentId != 0) {
				
				id = zenarioO.itemHierarchyArray[parentId][0];
				
				if (id !== undefined
				 && $(get('organizer_item_' + id)).is(':visible')) {
					zenarioO.openItemsInHierarchy[parentId] = true;
				}
			}
		}
	}
};

//Check to see if any items were previously selected in this panel, and select them again if so
zenarioO.loadSelection = function() {
	var n = 0,
		selectedItem = false;
	
	if (zenarioO.branches[zenarioO.branches.length-1].selected_items[zenarioO.path]) {
		zenarioO.selectedItems = zenarioO.branches[zenarioO.branches.length-1].selected_items[zenarioO.path];
		
		foreach (zenarioO.selectedItems as var i) {
			//Remove any items that have disappeared since this panel was last shown
			if (!zenarioO.focus.items[i]) {
				delete zenarioO.selectedItems[i];
			
			} else {
				if (++n == 1) {
					selectedItem = i;
				}
			}
		}
	} else {
		zenarioO.selectedItems = {};
	}
	
	if (zenarioO.branches[zenarioO.branches.length-1].open_items[zenarioO.path]) {
		zenarioO.openItemsInHierarchy = zenarioO.branches[zenarioO.branches.length-1].open_items[zenarioO.path];
	} else {
		zenarioO.openItemsInHierarchy = {};
	}
	
	//Set the "last clicked" to the first item that we find as a work-around for not actually knowing what it should be
	if (!zenarioO.lastClicked && selectedItem) {
		zenarioO.lastClicked = selectedItem;
	}
	
	//If multiple-select is not enabled, ensure we've not got too many items selected
	if (!zenarioO.multipleSelectEnabled && n > 1) {
		zenarioO.selectedItems = {};
	}
	
	return zenarioO.lastClicked = selectedItem;
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
zenarioO.saveSearch = function() {
	zenarioO.branches[zenarioO.branches.length-1].searches[zenarioO.path] = zenarioO.searchTerm;
};

zenarioO.saveAdvancedSearch = function(search) {
	zenarioO.branches[zenarioO.branches.length-1].adv_searches[zenarioO.path] = search;
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
	
	var visibility = 'hidden';
	zenarioO.searchTerm = searchTerm;
	zenarioO.markIfViewIsFiltered();
	
	if (zenarioO.server_side
	 && zenarioO.view_mode == 'hierarchy') {
		//Don't allow searching in hierarchy view if the searching is server-side
		
	} else {
		if (zenarioO.focus && zenarioO.focus.columns) {
			foreach (zenarioO.focus.columns as var c) {
				if (!zenarioO.isInfoTag(c) && engToBoolean(zenarioO.focus.columns[c].searchable)) {
					visibility = 'visible';
				}
			}
		}
	}
	
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
	
	zenarioO.saveSearch();
};

zenarioO.itemJustDoubleClicked = false;
zenarioO.itemDoubleClick = function(id, e) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
		return false;
	}
	
	zenarioA.stop(e);
	
	//Stop multiple clicks
	if (zenarioO.itemJustDoubleClicked) {
		return false;
	} else {
		zenarioO.itemJustDoubleClicked = true;
		setTimeout(
			function() {
				zenarioO.itemJustDoubleClicked = false;
			}, zenarioO.itemDoubleClickTime);
	}
	
	if (zenarioO.itemClickTimeout) {
		clearTimeout(zenarioO.itemClickTimeout);
	}
	zenarioO.itemClicked = true;
	
	if (!zenarioO.focus || !zenarioO.focus.items[id]) {
		return false;
	}
	
	zenarioO.itemDoubleClickAction(id);
	
	return false;
};

zenarioO.itemDoubleClickLink = function(id) {
	var link = zenarioO.focus.items[id].link,
		panel = zenarioO.focus.items[id].panel;
	
	if (link === undefined
	 && panel === undefined
	 && zenarioO.focus.item) {
		link = zenarioO.focus.item.link;
		panel = zenarioO.focus.item.panel;
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

zenarioO.itemDoubleClickAction = function(id) {
	var link = zenarioO.itemDoubleClickLink(id);
	
	if (link) {
		if (!engToBoolean(link.unselect_items)) {
			zenarioO.selectedItems = {};
			zenarioO.selectedItems[id] = true;
			zenarioO.saveSelection();
		}

		zenarioA.action(zenarioO, zenarioO.focus.items[id], true, true, link);
		return true;
	} else {
		return false;
	}
};

zenarioO.topLevelClick = function(id, j, first) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
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
	if (!window.zenarioOSelectMode && zenarioO.focus && zenarioO.focus.trash && !engToBoolean(zenarioO.focus.trash.empty)) {
		zenarioA.action(zenarioO, zenarioO.focus.trash, false, true);
	}
	
	return false;
};

zenarioO.collectionButtonClick = function(id) {
	if (zenarioO.focus && zenarioO.focus.collection_buttons && zenarioO.focus.collection_buttons[id]) {
		zenarioA.action(zenarioO, zenarioO.focus.collection_buttons[id], false, true);
	}
};

zenarioO.itemButtonClick = function(id) {
	if (zenarioO.focus && zenarioO.focus.item_buttons && zenarioO.focus.item_buttons[id]) {
		zenarioA.action(zenarioO, zenarioO.focus.item_buttons[id], true, true);
	}
};

zenarioO.inlineButtonClick = function(id, itemId) {
	if (zenarioO.focus && zenarioO.focus.inline_buttons && zenarioO.focus.inline_buttons[id]) {
		zenarioO.selectSingleItem(itemId);
		zenarioO.saveSelection();
		zenarioA.action(zenarioO, zenarioO.focus.inline_buttons[id], true, true);
	}
};

zenarioO.toggleQuickFilter = function(id, turnOn) {

	var filter,
		c,
		filterType;

	if (!zenarioO.focus
	 || !zenarioO.focus.quick_filter_buttons
	 || !(filter = zenarioO.focus.quick_filter_buttons[id])
	 || !(c = filter.column)
	 || !(filterType = zenarioO.getColumnFilterType(c))
	 || !(zenarioO.checkIfColumnPickerChangesAreAllowed(c))
	 ) {
		return;
	}
	
	//Work out whether this filter should be enabled
	if (engToBoolean(filter.remove_filter)) {
		turnOn = false;
	
	} else if (turnOn === undefined) {
		turnOn = !zenarioO.quickFilterEnabled(id);
	}
	
	zenarioO.setFilterValue('shown', c, turnOn);
	zenarioO.clearFilter(c);
	
	if (turnOn) {
		zenarioO.setFilterValue('not', c, engToBoolean(filter.invert));
	
		if (filterType == 'yes_or_no') {
			zenarioO.setFilterValue('yes_or_no_', c, 1);
	
		} else if (filterType == 'enum' && filter.value !== undefined) {
			zenarioO.setFilterValue('enum_', c, filter.value);
	
		} else if (filterType == 'search' && filter.value !== undefined) {
			zenarioO.setFilterValue('searchcol_', c, filter.value);
		}
	}

	//Refresh to update the panel with the changes
	zenarioO.refreshAndShowPage();
};

//Check whether a specific quick-filter button is enabled
zenarioO.quickFilterEnabled = function(id) {

	var filter,
		c,
		filterType;

	if ((filter = zenarioO.focus.quick_filter_buttons[id])
	 && (c = filter.column)
	 && (filterType = zenarioO.getColumnFilterType(c))) {
		
		if (engToBoolean(filter.remove_filter)) {
			return !zenarioO.getFilterValue('shown', c);
		
		} else {
			if (zenarioO.getFilterValue('shown', c)
			 && engToBoolean(filter.invert) == engToBoolean(zenarioO.getFilterValue('not', c))) {
				
				if (filterType == 'yes_or_no') {
					return true;
	
				} else if (filterType == 'enum' && filter.value !== undefined) {
					return zenarioO.getFilterValue('enum_', c) === filter.value;
	
				} else if (filterType == 'search' && filter.value !== undefined) {
					return zenarioO.getFilterValue('searchcol_', c) === filter.value;
				}
			}
		}
	}
	
	return false;
};



//Apply merge fields to a message, using the currently selected item's columns as fields
zenarioO.applyMergeFields = function(string, escapeHTML, i, keepNewLines) {
	
	var string2 = string;
	
	if (i === undefined) {
		i = zenarioO.getKeyId();
	}
	
	foreach (zenarioO.focus.items[i] as var c) {
		if (string.indexOf('[[' + c + ']]') != -1) {
			var columnValue = zenarioO.columnValue(i, c, !escapeHTML);
			
			if (!keepNewLines && columnValue !== undefined) {
				columnValue = columnValue.replace(/\n/g, ' ');
			}
			
			while (string != (string2 = string.replace('[[' + c + ']]', columnValue))) {
				string = string2;
			}
		}
	}
	
	return string;
};


//In grid view, there is not much space for the labels, and long words would cut into the space of the next labels,
//causing text to overlap and look horrible.
//These two functions are used to try and put line-breaks in at appropriate places, to stop words getting too long
zenarioO.applySmallSpaces = function(text) {
	var pos, out = '';
	while ((pos = text.search(/([a-z][A-Z]|[a-zA-Z][^a-zA-Z\)]|[^a-zA-Z\(][a-zA-Z]|[0-9][^0-9\)]|[^0-9\(][0-9])/)) != -1) {
		out += zenarioO.maxLengthString(htmlspecialchars(text.substr(0, pos + 1)), 15);
		
		if (text.substr(0, pos + 2).search(/\s/) == -1) {
			out += '<span style="font-size: 1px;"> </span>';
		}
		
		text = text.substr(pos + 1);
	}
	
	out += zenarioO.maxLengthString(htmlspecialchars(text), 15);
	
	return out;
};

zenarioO.maxLengthString = function(text, length) {
	var out = '';
	while (text.length > length) {
		out += text.substr(0, length) + '<span style="font-size: 1px;"> </span>';
		text = text.substr(length);
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
		
		$.post(URLBasePath + zenarioO.actionTarget, zenarioO.actionRequests, function(message) {
			//Check that this isn't an out-of-date request that has come in syncronously via AJAX
			if (goNum != zenarioO.goNum) {
				return;
			}

			if (message) {
				if (zenarioA.showMessage(message, undefined, 'error') === false) {
					return;
				}
			}
			
			zenarioO.selectCreatedIds();
		}, 'text');
	}
	zenarioO.actionTarget = false;
	delete zenarioO.actionRequests;
};

zenarioO.uploadComplete = function() {
	zenarioO.selectCreatedIds();
};

zenarioO.selectCreatedIds = function() {
	
	var newIds;
	if (newIds = zenario.nonAsyncAJAX(URLBasePath + 'zenario/ajax.php?method_call=getNewId', true, true)) {
		zenarioO.deselectAllItems();
		zenarioO.selectedItems = newIds;
		zenarioO.saveSelection();
	}
	
	get('organizer_preloader_circle').style.display = 'none';
	
	
	var items = '';
	foreach (zenarioO.selectedItems as var i) {
		items += (items? ',' : '') + i;
	}
	
	//Clear the local storage, as there have probably just been changes
	delete zenario.rev;
	
	if (newIds && items) {
		//Make sure show the specific items if possible
		zenarioO.go(zenarioO.path + '//' + items);
	} else {
		//Otherwise use a normal refresh.
		zenarioO.refresh();
	}
};


//Refresh the current view
zenarioO.lastActivity = false;
zenarioO.refreshIsPeriodic = false;
zenarioO.refresh = function(periodic, allowCache, runFunctionAfter) {
	
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
	zenarioO.recordOpenItems();
	zenarioO.saveOpenItems();
	
	zenarioO.refreshToPage = zenarioO.page;
	zenarioO.refreshIsPeriodic = periodic;
	
	var path = zenarioO.path;
	if (zenarioO.inCloseUpView) {
		path += '//' + zenarioO.itemToSelect + '/';
	}
	
	zenarioO.go(path, undefined, undefined, undefined, undefined, undefined, !allowCache, undefined, undefined, runFunctionAfter, periodic);
};

//Go to the change password section
zenarioO.changePassword = function() {
	zenarioO.reload(undefined, true, 'change_password');
};

//Reload Storekeeper, making sure to go via the admin login page in case a login/db_update is needed
zenarioO.reload = function(hash, dontAutoDetectMode, task) {
	if (zenarioA.uploading) {
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
		'zenario/admin/welcome.php?task=' + task + '&sk=' + encodeURIComponent(hash) +
		(zenarioA.fromCID? '&fromCID=' + zenarioA.fromCID + '&fromCType=' + zenarioA.fromCType : '');
};

zenarioO.refreshToShowItem = function(itemId) {
	if (zenarioA.uploading) {
		return false;
	}
	
	//If this is the admin or the site settings panel, do a SK reload instead
	if (zenarioO.path == 'zenario__administration/panels/site_settings') {
		zenarioO.reload();
		return;
	}
	
	//Escape the item id if needed
	if (zenarioO.focus.db_items && zenarioO.focus.db_items.encode_id_column) {
		itemId = zenario.encodeItemIdForStorekeeper(itemId);
	}
	
	var itemIds = (itemId + '').split(',');
	zenarioO.selectedItems = {};
	
	foreach (itemIds as var i) {
		zenarioO.selectedItems[itemIds[i]] = true;
	}
	
	zenarioO.saveSelection();
	zenarioO.stopRefreshing();
	
	//Clear the local storage, as there have probably just been changes
	delete zenario.rev;
	
	if (zenarioO.inCloseUpView) {
		zenarioO.go(zenarioO.path + '//' + itemId + '/');
	} else {
		zenarioO.go(zenarioO.path + '//' + itemId);
	}
};

//Keep refreshing panels that can change
zenarioO.refreshing = false;

//(Periodic refresh is now disabled)
//zenarioO.periodicRefresh = function() {
//	if (zenarioA.uploading || zenarioO.changingHierarchy) {
//		return false;
//	}
//	
//	zenarioO.stopRefreshing();
//	zenarioO.refreshing =
//		setTimeout(
//			function() {
//				zenarioO.refresh(true, true);
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
	zenarioO.refresh();
};


//Get the current collection key, as well as the current item id(s) if this is something on the item level
zenarioO.getKey = function(itemLevel) {
	
	var key = {};
	if (zenarioO.focus.key) {
		foreach (zenarioO.focus.key as var i) {
			if (!zenarioO.isInfoTag(i)) {
				key[i] = zenarioO.focus.key[i];
			}
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
	
	if (itemLevel && zenarioO.selectedItems) {
		key.id = zenarioO.getKeyId();
	}
	
	if (zenarioA.openedInIframe && window.parent && window.parent.zenario) {
		if (window.parent.zenarioO.init) {
			var parentKey = window.parent.zenarioO.getKey(true);
			foreach (parentKey as var i) {
				key['parent__' + i] = parentKey[i];
			}
		} else if (window.parent.zenario.cID) {
			key.parent__cID = window.parent.zenario.cID;
			key.parent__cType = window.parent.zenario.cType;
			key.parent__cVersion = window.parent.zenario.cVersion;
		}
	}
	
	return key;
};

zenarioO.getKeyId = function(limitOfOne) {
	
	var id = '',
		comma = '';
	
	if (zenarioO.selectedItems) {
		foreach (zenarioO.selectedItems as var i) {
			if (!zenarioO.isInfoTag(i)) {
				if (limitOfOne) {
					return i;
				} else {
					id += comma + i;
					comma = ',';
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

zenarioO.changeView = function(mode) {
	
	if (zenarioA.uploading
	 || !zenarioO.focus
	 || zenarioO.view_mode == 'hierarchy'
	 || (zenarioO.focus.force_view_mode && zenarioO.focus.force_view_mode != mode)
	 || (mode == 'grid' && engToBoolean(zenarioO.focus.disable_grid_view))
	 || (mode == 'list' && engToBoolean(zenarioO.focus.disable_list_view))) {
		return false;
	}
	
	zenarioO.changeViewMode = mode;
	
	//Check if the prefs are up to date. If not, refresh the panel first.
	if (zenarioO.checkPrefs()) {
		zenarioO.changeView2();
	} else {
		zenarioO.refresh(undefined, undefined, zenarioO.changeView2);
	}
};

zenarioO.changeView2 = function() {
	zenarioO.prefs[zenarioO.voPath].view_mode = zenarioO.changeViewMode;
	zenarioO.view_mode = zenarioO.changeViewMode;
	
	zenarioO.inCloseUpView = false;
	zenarioO.scrollToPageTop = true;
	zenarioO.drawPanel();
	
	zenarioO.savePrefs();
	return false;
};

zenarioO.showViewOptions = function(e) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
		return false;
	}
	
	if (!zenarioO.focus.columns) {
		return false;
	}
	
	//Check if the prefs are up to date. If not, refresh the panel first.
	if (zenarioO.checkPrefs()) {
		zenarioO.showViewOptions2();
	} else {
		zenarioO.refresh(undefined, undefined, zenarioO.showViewOptions2);
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
	
	if (!zenarioO.focus
	 || !zenarioO.focus.columns
	 || !zenarioO.focus.columns[c]) {
		return false
	}

	var filterType = false;
	
	if (zenarioO.focus.columns[c].format == 'date'
	 || zenarioO.focus.columns[c].format == 'datetime'
	 || zenarioO.focus.columns[c].format == 'datetime_with_seconds') {
		filterType = 'date';
	
	} else if (zenarioO.focus.columns[c].format == 'yes_or_no') {
		filterType = 'yes_or_no';
	
	} else if (
		(zenarioO.focus.columns[c].format == 'enum' && zenarioO.focus.columns[c].values)
	 || zenarioO.focus.columns[c].format == 'language_english_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_english_name'
	 || zenarioO.focus.columns[c].format == 'language_local_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_local_name'
	) {
		filterType = 'enum';
	
	} else if (engToBoolean(zenarioO.focus.columns[c].searchable)) {
		filterType = 'search';
	}
	
	return filterType;
};

zenarioO.setViewOptions = function() {
	
	if (!zenarioO.CSVExportEnabled) {
		zenarioO.showCSVInViewOptions = false;
	}
	
	var html = '';
	
	zenarioAB.templatePrefix = 'zenario_filters';
	zenarioAB.shownTab = 'cp';
	zenarioAB.focus = {
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
	
	zenarioAB.focus.tabs.cp.fields.showcol__title_ = {
		ord: -3,
		full_width: true,
		pre_field_html: '<div class="organizer_colPickerTitle_show" style="">',
		snippet: {html: phrase.show},
		post_field_html: '</div>'
	};
	
	if (zenarioO.showCSVInViewOptions) {
		zenarioAB.focus.tabs.cp.fields.showcsv__title_ = {
			ord: -2,
			same_row: true,
			pre_field_html: '<div class="organizer_colPickerTitle_csv">',
			snippet: {html: phrase.csv},
			post_field_html: '</div>'
		};
	}
	
	zenarioAB.focus.tabs.cp.fields.sortcol__title_ = {
		ord: -1,
		same_row: true,
		pre_field_html: '<div class="organizer_colPickerTitle_sort">',
		snippet: {html: phrase.sort},
		post_field_html: '</div>'
	};
	
	var c, colNo, lastCol = false, lastColName = false;
	foreach (zenarioO.sortedColumns as colNo => c) {
		
		if (zenarioO.isShowableColumn(c, false, zenarioO.showCSVInViewOptions)) {
			
			zenarioAB.focus.tabs.cp.fields['start_of_row__' + c] = {
				ord: 100 * colNo,
				full_width: true,
				snippet: {html: ''}
			}
			
			zenarioAB.focus.tabs.cp.fields['showcol_' + c] = {
				ord: 100 * colNo + 4,
				same_row: true,
				type: 'toggle',
				onclick: "zenarioO.showHideColumn(!" + engToBoolean(zenarioO.shownColumns[c]) + ", '" + htmlspecialchars(c) + "'); zenarioO.setViewOptions();",
				value: zenarioO.shownColumns[c]? 'Hide' : 'Show',
				title: phrase.showCol,
				'class': (zenarioO.view_mode == 'grid' ||
						 engToBoolean(zenarioO.focus.columns[c].always_show)?
						 	'notused ' : '') + (zenarioO.shownColumns[c]? 'zen_col_hidden' : 'zen_col_shown')
			};
			
			if (zenarioO.showCSVInViewOptions) {
				delete zenarioAB.focus.tabs.cp.fields['showcol_' + c].onclick;
				
				if (engToBoolean(engToBoolean(zenarioO.focus.columns[c].server_side_only))) {
					zenarioAB.focus.tabs.cp.fields['showcol_' + c].style = 'visibility: hidden;';
				} else {
					zenarioAB.focus.tabs.cp.fields['showcol_' + c].disabled = 'disabled';
				}
				
				var value = zenarioO.shownColumns[c];
				
				if (zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV !== undefined
				 && zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV[c] !== undefined) {
					value = zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV[c];
				}
				
				zenarioAB.focus.tabs.cp.fields['showcsv_' + c] = {
					ord: 100 * colNo + 5,
					same_row: true,
					type: 'checkbox',
					onclick: "zenarioO.showHideColumnInCSV(this, '" + htmlspecialchars(c) + "');",
					value: value,
					'class': zenarioO.view_mode == 'grid' ||
							 engToBoolean(zenarioO.focus.columns[c].always_show)?
								'notused' : ''
				};
			}
			
			//if (zenarioO.reordering) {
			//	zenarioAB.focus.tabs.cp.fields['sortcol_' + c] = {
			//		ord: 100 * colNo + 6,
			//		same_row: true,
			//		type: 'toggle',
			//		value: ' ',
			//		'class': zenarioO.sortBy == c? (zenarioO.sortDesc? 'sortcol_desc' : 'sortcol_asc') : ' ',
			//		style: 'cursor: default;',
			//		pre_field_html: '<span class="organizer_sort_col_wrap">',
			//		post_field_html: '<label for="sortcol_' + c + '">' + htmlspecialchars(zenarioO.focus.columns[c].title) + '</label></span>'
			//	};
			//
			//} else if (zenarioO.canSortColumn(c)) {
			//	zenarioAB.focus.tabs.cp.fields['sortcol_' + c] = {
			//		ord: 100 * colNo + 6,
			//		same_row: true,
			//		type: 'toggle',
			//		value: ' ',
			//		'class': zenarioO.sortBy == c? (zenarioO.sortDesc? 'sortcol_desc' : 'sortcol_asc') : 'sortcol',
			//		onclick: "zenarioO.changeSortOrder('" + htmlspecialchars(c) + "');",
			//		pre_field_html: '<span class="organizer_sort_col_wrap" title="' + phrase.sortByCol + '">',
			//		post_field_html: '<label style="cursor: pointer;" for="sortcol_' + c + '">' + htmlspecialchars(zenarioO.focus.columns[c].title) + '</label></span>'
			//	};
			//
			//} else {
			//	zenarioAB.focus.tabs.cp.fields['sortcol_' + c] = {
			//		ord: 100 * colNo + 6,
			//		same_row: true,
			//		type: 'toggle',
			//		value: ' ',
			//		style: 'cursor: default;',
			//		pre_field_html: '<span class="organizer_sort_col_wrap">',
			//		post_field_html: '<label>' + htmlspecialchars(zenarioO.focus.columns[c].title) + '</label></span>'
			//	};
			//}
			
			zenarioAB.focus.tabs.cp.fields['sortcol_' + c] = {
				ord: 100 * colNo + 6,
				same_row: true,
				snippet: {
					html: '<label class="zenario_filter_column_name" for="showcol_' + c + '">' + htmlspecialchars(zenarioO.focus.columns[c].title) + '</label>'
				}
			};
			
			if (zenarioO.canFilterColumn(c)) {
				
				var hidden = !zenarioO.getFilterValue('shown', c);
				var hiddenPreviously = hidden;//?toggledColumn == c? !hidden : hidden;
				var invertLink =
					'<a class="' + (zenarioO.getFilterValue('not', c)? 'organizer_inverter organizer_not' : 'organizer_inverter') + '"' +
						' title="' + phrase.invertFilter + '"' +
						' onclick="zenarioO.invertFilter(this, \'' + htmlspecialchars(c) + '\', \'' + htmlspecialchars(zenarioO.focus.columns[c].format) + '\');">';
				
				if (zenarioO.focus.columns[c].format == 'date'
				 || zenarioO.focus.columns[c].format == 'datetime'
				 || zenarioO.focus.columns[c].format == 'datetime_with_seconds') {
					zenarioAB.focus.tabs.cp.fields['date_after_col_' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_date_filters_for_field',
						label: phrase.after,
						type: 'date',
						_change_filter_on_change: true,
						value: zenarioO.getFilterValue('date_after_col_', c),
						hidden: hidden,
						_h: hiddenPreviously
					};
					zenarioAB.focus.tabs.cp.fields['date_before_col_' + c] = {
						ord: 100 * colNo + 8,
						row_class: 'zenario_date_filters_for_field',
						label: phrase.before,
						type: 'date',
						_change_filter_on_change: true,
						value: zenarioO.getFilterValue('date_before_col_', c),
						hidden: hidden,
						_h: hiddenPreviously
					};
				
				} else if (zenarioO.focus.columns[c].format == 'yes_or_no') {
					//Attempt to add a colon to the title of the column
					var label = ('' + zenarioO.focus.columns[c].title);
					if (label.indexOf(':') == -1) {
						label += ':';
						label = label.replace(/\s*:/, ':');
					}
					
					zenarioAB.focus.tabs.cp.fields['yes_or_no_' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field yes_or_no',
						snippet: {
							html: invertLink + (zenarioO.getFilterValue('not', c)? phrase.no : phrase.yes) + '</a>'
						},
						hidden: hidden,
						_h: hiddenPreviously
					};
				
				} else if (
					(zenarioO.focus.columns[c].format == 'enum' && zenarioO.focus.columns[c].values)
				 || zenarioO.focus.columns[c].format == 'language_english_name_with_id'
				 || zenarioO.focus.columns[c].format == 'language_english_name'
				 || zenarioO.focus.columns[c].format == 'language_local_name_with_id'
				 || zenarioO.focus.columns[c].format == 'language_local_name'
				) {
					var values;
					
					if (zenarioO.focus.columns[c].format == 'enum') {
						//Get the list of values for this column, adding on an empty value if one is not present
						values = zenarioO.focus.columns[c].values;
						if (!values[''] && !values[0]) {
							values = {};
							values[''] = phrase.selectListSelect;
							
							foreach (zenarioO.focus.columns[c].values as var v) {
								values[v] = zenarioO.focus.columns[c].values[v];
							}
						}
					
					} else {
						values = {};
						values[''] = phrase.selectListSelect;
						
						foreach (zenarioA.lang as var v) {
							if (zenarioA.lang[v].enabled) {
								values[v] = zenarioA.lang[v].name;
								
								if (zenarioO.focus.columns[c].format == 'language_english_name_with_id'
								 || zenarioO.focus.columns[c].format == 'language_local_name_with_id') {
									values[v] += ' (' + v + ')';
								}
							}
						}
					}
					
					zenarioAB.focus.tabs.cp.fields['enum_' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field enum',
						label: invertLink + (zenarioO.getFilterValue('not', c)? phrase.isnt : phrase.is) + '</a>',
						type: 'select',
						_change_filter_on_change: true,
						values: values,
						value: zenarioO.getFilterValue('enum_', c),
						hidden: hidden,
						_h: hiddenPreviously
					};
				
				} else if (engToBoolean(zenarioO.focus.columns[c].searchable)) {
					zenarioAB.focus.tabs.cp.fields['searchcol_' + c] = {
						ord: 100 * colNo + 7,
						row_class: 'zenario_filters_for_field',
						label: invertLink + (zenarioO.getFilterValue('not', c)? phrase.notLike : phrase.like) + '</a>',
						type: 'text',
						onkeyup: 'zenarioAB.changeFiltersAfterDelay();',
						value: zenarioO.getFilterValue('searchcol_', c),
						hidden: hidden,
						_h: hiddenPreviously
					};
				}
				
				if (zenarioO.getColumnFilterType(c)) {
					zenarioAB.focus.tabs.cp.fields['togglefilter_' + c] = {
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
				zenarioAB.focus.tabs.cp.fields['up__' + c] = {
					ord: 100 * colNo + 1,
					same_row: true,
					pre_field_html: '<div class="organizer_mov_col">',
					type: 'toggle',
					value: ' ',
					'class': 'movcol_up',
					style: 'visibility: hidden;',
					title: phrase.moveColForward
				};
				
				zenarioAB.focus.tabs.cp.fields['down__' + c] = {
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
					zenarioAB.focus.tabs.cp.fields['up__' + c].style = '';
					zenarioAB.focus.tabs.cp.fields['up__' + c].onclick = 'zenarioO.switchColumnOrder(' + colNo + ', ' + lastCol + ', true)';
					
					zenarioAB.focus.tabs.cp.fields['down__' + lastColName].style = '';
					zenarioAB.focus.tabs.cp.fields['down__' + lastColName].onclick = 'zenarioO.switchColumnOrder(' + colNo + ', ' + lastCol + ', true)';
				}
			}
			lastCol = colNo;
			lastColName = c;
		}
	}
	
	
	var showResetButton = false;
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
	if (zenarioO.prefs[zenarioO.voPath]) {
		foreach (zenarioO.prefs[zenarioO.voPath] as var p) {
			if (p && p != 'adv_searches') {
				showResetButton = true;
				break;
			}
		}
	}
	
	if (showResetButton) {
		zenarioAB.focus.tabs.cp.fields.reset = {
			ord: 100 * colNo + 11,
			full_width: true,
			type: 'toggle',
			onclick: "zenarioO.resetPrefs();",
			value: phrase.reset
		};
	}
	
	if (zenarioO.CSVExportEnabled) {
		zenarioAB.focus.tabs.cp.fields.toggle_csv = {
			ord: 100 * colNo + 12,
			full_width: true,
			same_row: showResetButton,
			type: 'toggle',
			style: 'float: right; width: auto;',
			onclick: "zenarioO.showCSVInViewOptions = !zenarioO.showCSVInViewOptions; zenarioO.setViewOptions();",
			value: zenarioO.showCSVInViewOptions? phrase.hideExport : phrase.showExport
		};
	}
	
	zenarioAB.sortTabs();
	html += zenarioAB.draw2(true);
	
	get('zenario_fbAdminViewModeOptions').innerHTML = html;
	zenario.addJQueryElements('#zenario_fbAdminViewModeOptions ', true);
	zenarioO.size(true);
};

/*zenarioO.showFilterOptions = function(c) {
	if (zenarioA.uploading || zenarioO.changingHierarchy || zenarioO.sortingColumn || zenarioO.resizingColumn) {
		return false;
	}
	
	if (!zenarioO.focus.columns) {
		return false;
	}
	
	//Check if the prefs are up to date. If not, refresh the panel first.
	if (zenarioO.checkPrefs()) {
		zenarioO.showFilterOptions2(c);
	} else {
		zenarioO.refresh(undefined, undefined, function() { zenarioO.showFilterOptions2(c) });
	}
	
	return false;
};

zenarioO.showFilterOptions2 = function(c) {
	var width = 300,
		left = 0,
		top = 0;
	zenarioA.openBox(false, 'zenario_column_filter', 'AdminColumnFilter', get('organizer_column__' + c), width, left, top, false, true, false, false, undefined, undefined, false, true);
	
	zenarioO.setFilterOptions(c);
	zenarioAB.focusFirstField();
};*/

zenarioO.canSortColumn = function(c) {
	if (zenarioO.reordering
	 || engToBoolean(zenarioO.focus.columns[c].disallow_sorting)
	 || (zenarioO.server_side && !zenarioO.focus.columns[c].db_column)) {
		return false;
	}
	return true;
};

zenarioO.canFilterColumn = function(c) {
	
	if (!zenarioO.focus.columns[c].db_column
	 || engToBoolean(zenarioO.focus.columns[c].server_side_only)
	 || engToBoolean(zenarioO.focus.columns[c].disallow_filtering)) {
		return false;
	}
	
	if (zenarioO.focus.columns[c].format == 'date'
	 || zenarioO.focus.columns[c].format == 'datetime'
	 || zenarioO.focus.columns[c].format == 'datetime_with_seconds') {
	} else if (zenarioO.focus.columns[c].format == 'yes_or_no') {
	} else if (
		(zenarioO.focus.columns[c].format == 'enum' && zenarioO.focus.columns[c].values)
	 || zenarioO.focus.columns[c].format == 'language_english_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_english_name'
	 || zenarioO.focus.columns[c].format == 'language_local_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_local_name'
	) {
	} else if (engToBoolean(zenarioO.focus.columns[c].searchable)) {
	} else {
		return false;
	}
	
	return true;
};

/*zenarioO.setFilterOptions = function(c) {
	
	if (!zenarioO.canFilterColumn(c)) {
		return false;
	}
	
	var html =
		'<div class="organizer_filterfields" id="organizer_filterfields">';
	
	zenarioAB.shownTab = 'cp';
	zenarioAB.focus = {
		tab: 'cp',
		tabs: {
			cp: {
				edit_mode: {
					on: true,
					enabled: true,
					always_on: true
				},
				fields: {}
			}
		}
	};
				
	var filterType = false,
		invertLink =
			'<a class="' + (zenarioO.getFilterValue('not', c)? 'inverter not' : 'inverter') + '"' +
				' title="' + phrase.invertFilter + '"' +
				' onclick="zenarioO.invertFilter(this, \'' + htmlspecialchars(c) + '\', \'' + htmlspecialchars(zenarioO.focus.columns[c].format) + '\');">';
	
	if (zenarioO.focus.columns[c].format == 'date' || zenarioO.focus.columns[c].format == 'datetime') {
		filterType = 'date';
		zenarioAB.focus.tabs.cp.fields['date_after_col_' + c] = {
			ord: 7,
			label: phrase.after,
			type: 'date',
			_change_filter_on_change: true,
			value: zenarioO.getFilterValue('date_after_col_', c)
		};
		zenarioAB.focus.tabs.cp.fields['date_before_col_' + c] = {
			ord: 8,
			label: phrase.before,
			type: 'date',
			_change_filter_on_change: true,
			value: zenarioO.getFilterValue('date_before_col_', c)
		};
	
	} else if (zenarioO.focus.columns[c].format == 'yes_or_no') {
		filterType = 'yes_or_no';
		
		//Attempt to add a colon to the title of the column
		var label = ('' + zenarioO.focus.columns[c].title);
		if (label.indexOf(':') == -1) {
			label += ':';
			label = label.replace(/\s*:/, ':');
		}
		
		zenarioAB.focus.tabs.cp.fields['yes_or_no_' + c] = {
			ord: 7,
			row_class: 'yes_or_no',
			label: htmlspecialchars(label),
			type: 'radios',
			_change_filter_on_change: true,
			values: {
				'yes': {
					ord: 1,
					label: phrase.yes
				},
				'no': {
					ord: 2,
					label: phrase.no
				}
			},
			value: zenarioO.getFilterValue('yes_or_no_', c)
		};
	
	} else if (
		(zenarioO.focus.columns[c].format == 'enum' && zenarioO.focus.columns[c].values)
	 || zenarioO.focus.columns[c].format == 'language_english_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_english_name'
	 || zenarioO.focus.columns[c].format == 'language_local_name_with_id'
	 || zenarioO.focus.columns[c].format == 'language_local_name'
	) {
		filterType = 'enum';
		var values;
		
		if (zenarioO.focus.columns[c].format == 'enum') {
			//Get the list of values for this column, adding on an empty value if one is not present
			values = zenarioO.focus.columns[c].values;
			if (!values[''] && !values[0]) {
				values = {};
				values[''] = phrase.selectListSelect;
				
				foreach (zenarioO.focus.columns[c].values as var v) {
					values[v] = zenarioO.focus.columns[c].values[v];
				}
			}
		
		} else {
			values = {};
			values[''] = phrase.selectListSelect;
			
			foreach (zenarioA.lang as var v) {
				if (zenarioA.lang[v].enabled) {
					values[v] = zenarioA.lang[v].name;
					
					if (zenarioO.focus.columns[c].format == 'language_english_name_with_id'
					 || zenarioO.focus.columns[c].format == 'language_local_name_with_id') {
						values[v] += ' (' + v + ')';
					}
				}
			}
		}
		
		zenarioAB.focus.tabs.cp.fields['enum_' + c] = {
			ord: 7,
			row_class: 'enum',
			label: invertLink + (zenarioO.getFilterValue('not', c)? phrase.isnt : phrase.is) + '</a>',
			type: 'select',
			_change_filter_on_change: true,
			values: values,
			value: zenarioO.getFilterValue('enum_', c)
		};
	
	} else if (engToBoolean(zenarioO.focus.columns[c].searchable)) {
		filterType = 'search';
		zenarioAB.focus.tabs.cp.fields['searchcol_' + c] = {
			ord: 7,
			label: invertLink + phrase.contains + '</a>',
			type: 'text',
			onkeyup: 'zenarioAB.changeFiltersAfterDelay();',
			value: zenarioO.getFilterValue('searchcol_', c)
		};
	
	} else {
		return false;
	}
	
	zenarioAB.focus.tabs.cp.fields['remove_filter_' + c] = {
		ord: 9,
		type: 'button',
		same_row: true,
		onclick: "zenarioO.clearFilter('" + jsEscape(c) + "'); zenarioA.closeBox('AdminColumnFilter'); zenarioO.refreshAndShowPage();",
		'class': zenarioO.filterSetOnColumn(c)? 'organizer_remove_filter organizer_remove_filter_active' : 'organizer_remove_filter organizer_remove_filter_inactive',
		value: phrase.clear
	};
	
	zenarioAB.focus.tabs.cp.fields['close_filter_' + c] = {
		ord: 9.1,
		type: 'button',
		same_row: true,
		onclick: "zenarioA.closeBox('AdminColumnFilter'); zenarioO.refreshAndShowPage();",
		'class': 'organizer_close_filter',
		value: ''
	};
	
	zenarioAB.sortTabs();
	html += zenarioAB.draw2(true);
	
	html += '</div>';
	
	get('zenario_fbAdminColumnFilter').innerHTML = html;
	zenario.addJQueryElements('#zenario_fbAdminColumnFilter ', true);
	zenarioO.size(true);
};*/

zenarioO.showHideColumn = function(show, c) {
	//Ignore requests to hide columns that are forced on
	if (engToBoolean(zenarioO.focus.columns[c].always_show)) {
		show = true;
	
	} else {
		//Ensure someone can never hide the last column!
		var count = 0;
		foreach (zenarioO.focus.columns as var i) {
			if (!zenarioO.isInfoTag(i)) {
				if (zenarioO.shownColumns[i]) {
					++count;
				}
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
	zenarioO.prefs[zenarioO.voPath].shownColumns = zenarioO.shownColumns;
	zenarioO.savePrefs();
	
	zenarioO.drawPanel();
};

zenarioO.resizeColumn = function(c, size) {
	if (zenarioO.shownColumns[c]) {
		size = Math.max(size, zenarioO.columnWidths.xxsmall);
		
		zenarioO.checkPrefs();
		if (!zenarioO.prefs[zenarioO.voPath].colSizes) {
			zenarioO.prefs[zenarioO.voPath].colSizes = {};
		}
		zenarioO.prefs[zenarioO.voPath].colSizes[c] = size;
		zenarioO.savePrefs();
	}
	
	zenarioO.drawPanel();
};

zenarioO.showHideColumnInCSV = function(el, c) {
	zenarioO.checkPrefs();
	
	if (!zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV) {
		zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV = {};
	}
	
	if (el.checked) {
		zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV[c] = true;
	} else {
		zenarioO.prefs[zenarioO.voPath].shownColumnsInCSV[c] = false;
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
	zenarioO.prefs[zenarioO.voPath].sortedColumns = zenarioO.sortedColumns;
	
	zenarioO.drawPanel();
	
	if (viewOptions) {
		zenarioO.setViewOptions();
	}
	
	zenarioO.savePrefs();
};

zenarioO.resetPrefs = function() {
	
	zenarioO.checkPrefs();
	if (zenarioO.prefs[zenarioO.voPath]) {
		if (zenarioO.prefs[zenarioO.voPath].adv_searches) {
			zenarioO.prefs[zenarioO.voPath] = {adv_searches: zenarioO.prefs[zenarioO.voPath].adv_searches};
		} else {
			zenarioO.prefs[zenarioO.voPath] = {};
		}
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
	if (zenarioO.reordering) {
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
	zenarioO.prefs[zenarioO.voPath].sortBy = zenarioO.sortBy;
	zenarioO.prefs[zenarioO.voPath].sortDesc = zenarioO.sortDesc;
	
	zenarioO.runSearch();
	
	//This line is not needed now we don't use the view options for sorting columns
	//zenarioO.setViewOptions();
	
	zenarioO.savePrefs(true);
};

zenarioO.possibleFilters = {searchcol_: true, enum_: true, date_after_col_: true, date_before_col_: true, yes_or_no_: true};

zenarioO.toggleFilter = function(el, c) {
	
	var shown,
		filterType = zenarioO.getColumnFilterType(c);
	
	if (!filterType
	 || !zenarioO.checkIfColumnPickerChangesAreAllowed(c)) {
		return;
	}
	
	//Toogle whether this filter is hidden or shown
	shown = !zenarioO.getFilterValue('shown', c);
	zenarioO.setFilterValue('shown', c, shown);
	
	//Unset values of hidden filters
	var refreshNeeded = false;
	if (!shown) {
		foreach (zenarioO.possibleFilters as var filter) {
			if (zenarioO.getFilterValue(filter, c)) {
				zenarioO.setFilterValue(filter, c, '')
				refreshNeeded = true;
			}
		}
		zenarioO.setFilterValue('not', c, false);
	}
	
	//yes_or_no type filters should turn on by default if activated
	if (filterType == 'yes_or_no') {
		if (shown) {
			refreshNeeded = true;
			zenarioO.setFilterValue('yes_or_no_', c, 1);
		}
	}
	
	
	zenarioO.setViewOptions(c);
	
	//Animate in/out the field that was just shown/hidden
	zenarioAB.hideShowFields(function() {
		zenarioO.size(true);
		//Focus a text field straight away if we can
		if (get('searchcol_' + c) && $(get('searchcol_' + c)).is(':visible')) {
			get('searchcol_' + c).focus();
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
	foreach (zenarioO.possibleFilters as var filter) {
		if (zenarioO.getFilterValue(filter, c)) {
			zenarioO.refreshAndShowPage();
		}
	}
};

zenarioO.filterSetOnColumn = function(c, filters) {
	foreach (zenarioO.possibleFilters as var filter) {
		if (zenarioO.getFilterValue(filter, c, filters)) {
			return true;
		}
	}
	return false;
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
	if (!zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path]) {
		zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path] = {};
	}
	if (!zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c]) {
		zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c] = {};
	}
	
	if (value === undefined) {
		delete zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c][filter];
	} else {
		zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c][filter] = value;
	}
	
	if ($.isEmptyObject(zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c])) {
		delete zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path][c];
	}
	if ($.isEmptyObject(zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path])) {
		delete zenarioO.branches[zenarioO.branches.length-1].filters[zenarioO.path];
	}
};

zenarioO.clearFilter = function(c) {
	foreach (zenarioO.possibleFilters as var filter) {
		zenarioO.setFilterValue(filter, c);
	}
	zenarioO.setFilterValue('not', c);
};


zenarioAB.changeFiltersAfterDelay = function() {
	zenario.actAfterDelayIfNotSuperseded('changeFilters', zenarioO.changeFilters, 700);
};

zenarioO.changeFilters = function() {
	
	//Don't allow the filters to be touched when reordering
	if (zenarioO.reordering) {
		zenarioO.setViewOptions();
		return;
	}
	
	
	var value;
	foreach (zenarioO.focus.columns as var c) {
		if (zenarioO.isShowableColumn(c)) {
			foreach (zenarioO.possibleFilters as var filter) {
				if (get(filter + c) || get(filter + c + '___yes')) {
					if (value = zenarioAB.readField(filter + c)) {
						zenarioO.setFilterValue(filter, c, value);
					} else {
						zenarioO.setFilterValue(filter, c, '');
					}
				}
				if (get('remove_filter_' + c)) {
					get('remove_filter_' + c).className =
						zenarioO.filterSetOnColumn(c)? 'organizer_remove_filter organizer_remove_filter_active' : 'organizer_remove_filter organizer_remove_filter_inactive'
				}
			}
		}
	}
	
	zenarioO.refreshAndShowPage();
};

zenarioO.hideViewOptions = function(e) {
	zenarioA.closeBox('AdminViewModeOptions');
	return false;
};


zenarioO.openAdvancedSearch = function(e) {
	if (zenarioA.uploading || zenarioO.changingHierarchy) {
		return false;
	}
	
	if (!zenarioO.focus.columns) {
		return false;
	}
	
	//Check if the prefs are up to date. If not, refresh the panel first.
	if (zenarioO.checkPrefs()) {
		zenarioO.openAdvancedSearch2();
	} else {
		zenarioO.refresh(undefined, undefined, zenarioO.openAdvancedSearch2);
	}
	
	return false;
};

zenarioO.openAdvancedSearch2 = function() {
	var width = 300,
		left = 0,
		top = -2;
	zenarioA.openBox(false, 'zenario_advanced_search', 'AdminAdvancedSearch', get('organizer_openAdvancedSearch'), width, left, top, false, true, false, false, undefined, undefined, false, true);
	
	zenarioO.openAdvancedSearch3();
};

zenarioO.openAdvancedSearch3 = function() {
	var html =
		'<div class="organizer_colsortheader">' + 
			'<a onclick="zenarioO.hideAdvancedSearch();"></a>' +
			phrase.advancedSearches + 
		'</div>' +
		'<div class="organizer_colsortfields" id="organizer_colsortfields">';
	
	//Loop through the saved searches. 
	var search = 0, adv_searches = 0;
	if (zenarioO.prefs[zenarioO.voPath].adv_searches) {
		foreach (zenarioO.prefs[zenarioO.voPath].adv_searches as search) {
			//Check that a search looks valid. Valid searches must have a descriptive name set, and a numeric key
			if (search == 1*search
			 && zenarioO.prefs[zenarioO.voPath].adv_searches[search].first_tab
			 && zenarioO.prefs[zenarioO.voPath].adv_searches[search].first_tab.name
			 && adv_searches < zenarioO.numberOfAdvancedSearches) {
				html +=
					'<p class="saved_search' + (zenarioO.advancedSearch == search? ' current_search' : '') + '">' +
						'<a class="organizer_search_name" onclick="zenarioO.openAdvancedSearchSettings(' + search + ');">' +
							htmlspecialchars(zenarioO.prefs[zenarioO.voPath].adv_searches[search].first_tab.name) +
						'</a>';
				
					if (search != zenarioO.advancedSearch) {
						html +=
						' <a class="organizer_delete_search" onclick="zenarioO.deleteAdvancedSearchConfirm(' + search + ');">' +
							phrase.deleteSearch +
						'</a>'
					}
					
					html +=
					'</p>';
				++adv_searches;
				
			} else {
				//Try to trim out any bad data
				zenarioO.deleteAdvancedSearch(search);
			}
		}
	}
	
	if (adv_searches < zenarioO.numberOfAdvancedSearches) {
		html +=
			'<p class="organizer_new_search"><a onclick="zenarioO.openAdvancedSearchSettings(' + ++search + ');">' +
				phrase.createSearch +
			'</a></p>';
	}
	
	html += '</div>';
	
	get('zenario_fbAdminAdvancedSearch').innerHTML = html;
	zenario.addJQueryElements('#zenario_fbAdminAdvancedSearch ', true);
	zenarioO.size(true);
};

zenarioO.deleteAdvancedSearchConfirm = function(search) {
	
	var html =
		phrase.deleteSearchConfirm.replace(
			/\[\[name\]\]/g,
			htmlspecialchars(zenarioO.prefs[zenarioO.voPath].adv_searches[search].first_tab.name));
	
	var buttonsHTML =
	'<input type="button" class="submit_selected" value="' + phrase.deleteSearch + '" onclick="zenarioO.deleteAdvancedSearch(' + search + '); zenarioO.openAdvancedSearch3();"/>' + 
	'<input type="button" class="submit" value="' + phrase.cancel + '"/>';
	
	
	zenarioA.floatingBox(html, buttonsHTML, true);
};

zenarioO.deleteAdvancedSearch = function(search) {
	zenarioO.checkPrefs();
	delete zenarioO.prefs[zenarioO.voPath].adv_searches[search];
	zenarioO.savePrefs();
};

zenarioO.clearAdvancedSearch = function(e) {
	if (zenarioO.advancedSearch) {
		zenarioO.saveAdvancedSearch(false);
		zenarioO.runSearch();
	}
};

zenarioO.hideAdvancedSearch = function(e) {
	zenarioA.closeBox('AdminAdvancedSearch');
	return false;
};

zenarioO.openAdvancedSearchSettings = function(search) {
	zenarioO.hideAdvancedSearch();
	
	var values = undefined;
	if (zenarioO.prefs[zenarioO.voPath].adv_searches) {
		values = zenarioO.prefs[zenarioO.voPath].adv_searches[search];
	}
	
	zenarioAB.open(
		'advanced_search',
		{search: search, storekeeper_path: zenarioO.path},
		undefined,
		values,
		function(key, values) {
			if (key.search
			 && key.search == 1*key.search
			 && values.first_tab
			 && values.first_tab.name) {
				zenarioO.checkPrefs();
				
				if (!zenarioO.prefs[zenarioO.voPath].adv_searches) {
					zenarioO.prefs[zenarioO.voPath].adv_searches = {};
				}
				
				zenarioO.prefs[zenarioO.voPath].adv_searches[key.search] = values;
				zenarioO.saveAdvancedSearch(key.search);
				
				zenarioO.savePrefs();
				zenarioO.runSearch();
			}
		});
};







//refine tags added by the CMS for location/tracking purposes
zenarioO.isInfoTag = function(i) {
	return i === 'back_link'
		|| i === 'class_name'
		|| i === 'count'
		|| i === 'ord';
};

zenarioO.isShowableColumn = function(c, shown, allowServerSide) {
	if (shown) {
		return zenarioO.shownColumns[c] && zenarioO.isShowableColumn(c);
	} else {
		return zenarioO.focus.columns[c] && !zenarioO.isInfoTag(c) && !zenarioA.hidden(zenarioO.focus.columns[c]) && zenarioO.focus.columns[c].title && (allowServerSide || !engToBoolean(zenarioO.focus.columns[c].server_side_only));
	}
};


//  Sorting Functions  //

zenarioO.createSortArray = function(name, toSort, column, desc) {
	//Build an array to sort, containing:
		//0: The item's actual index
		//1: The value to sort by
		//2: Whether this value is numeric
	var value,
		format = false,
		numeric;
	
	if (toSort == 'items' && zenarioO.focus.columns && zenarioO.focus.columns[column]) {
		format = zenarioO.focus.columns[column].format;
	}
	
	zenarioO[name] = [];
	if (zenarioO.focus[toSort]) {
		foreach (zenarioO.focus[toSort] as var i) {
			if (!zenarioO.isInfoTag(i) && zenarioO.focus[toSort][i]) {
				//Check if the value is a number, and if so make sure that it is numeric so it is sorted numericaly
				value = zenarioO.focus[toSort][i][column];
				
				if (format == 'true_or_false' || format == 'yes_or_no') {
					zenarioO[name].push([i, engToBoolean(value), true]);
				
				} else if (format != 'remove_zero_padding' && value == (numeric = 1*value)) {
					zenarioO[name].push([i, numeric, true]);
				
				} else if (value) {
					zenarioO[name].push([i, value.toLowerCase(), false]);
				
				} else {
					zenarioO[name].push([i, 0, true]);
				}
			}
		}
	}
	
	//Sort this array
	if (desc) {
		zenarioO[name].sort(zenarioO.sortArrayDesc);
	} else {
		zenarioO[name].sort(zenarioO.sortArray);
	}
	
	//Remove fields that were just there to help sort
	foreach (zenarioO[name] as var i) {
		zenarioO[name][i] = zenarioO[name][i][0];
	}
};

//A copy and paste of the above function, for the top level Menu Nodes
zenarioO.createSortArrayTopLevel = function(name) {
	var value,
		numeric;
	zenarioO[name] = [];
	foreach (zenarioO.map as var i) {
		if (!zenarioO.isInfoTag(i)) {
			//Check if the value is a number, and if so make sure that it is numeric so it is sorted numericaly
			value = zenarioO.map[i].ord;
			
			if (value == (numeric = 1*value)) {
				zenarioO[name].push([i, numeric, true]);
			} else if (value) {
				zenarioO[name].push([i, value.toLowerCase(), false]);
			} else {
				zenarioO[name].push([i, 0, true]);
			}
		}
	}
	
	//Sort this array
	zenarioO[name].sort(zenarioO.sortArray);
	
	//Remove fields that were just there to help sort
	foreach (zenarioO[name] as var i) {
		zenarioO[name][i] = zenarioO[name][i][0];
	}
};

//A copy and paste of the above function, for the second level Menu Nodes
zenarioO.createSortArraySecondLevel = function(name, topLevelItem) {
	var value,
		numeric;
	
	zenarioO[name] = [];
	if (zenarioO.map[topLevelItem].nav) {
		foreach (zenarioO.map[topLevelItem].nav as var i) {
			if (!zenarioO.isInfoTag(i)) {
				//Check if the value is a number, and if so make sure that it is numeric so it is sorted numericaly
				value = zenarioO.map[topLevelItem].nav[i].ord;
				
				if (value == (numeric = 1*value)) {
					zenarioO[name].push([i, numeric, true]);
				} else if (value) {
					zenarioO[name].push([i, value.toLowerCase(), false]);
				} else {
					zenarioO[name].push([i, 0, true]);
				}
			}
		}
	}
	
	//Sort this array
	zenarioO[name].sort(zenarioO.sortArray);
	
	//Remove fields that were just there to help sort
	foreach (zenarioO[name] as var i) {
		zenarioO[name][i] = zenarioO[name][i][0];
	}
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
    foreach (zenarioO.selectedItems as var id) {
    	if (!zenarioO.focus.items[id]) {
    		return false
    	
    	} else if (!condition(id, zenarioO.focus.items[id])) {
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
	
	if (!zenarioO.focus
	 || !zenarioO.focus.columns
	 || zenarioO.focus.columns[c] === undefined
	 || zenarioO.focus.items[i] === undefined) {
		return '';
	}
	
	return zenarioO.focus.items[i][c];
};

zenarioO.columnValue = function(i, c, dontHTMLEscape) {
	
	if (!zenarioO.focus || !zenarioO.focus.columns || zenarioO.focus.columns[c] === undefined) {
		return '';
	}
	
	var value = zenarioO.focus.items[i][c];
	if (value === undefined || value === false || value === null) {
		value = '';
	} else {
		value = '' + value;
	}
	
	
	//Is this item an item link..?
	var item_link = zenarioO.focus.columns[c].item_link,
		isSKLink = true,
		isURL = false;
	
	if (item_link) {
		var item = false;
		if (item_link == 'menu_item') {
			var lang = zenarioO.itemLanguage(i);
			if (value && zenarioO.menuItems[lang] && (item = zenarioO.menuItems[lang].items) && (item = item[value])) {}
			
		} else if (item_link == 'content_item' || item_link == 'content_item_or_url') {
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
		}
		
		if (item) {
			if (dontHTMLEscape) {
				return item.name;
			} else {
				
				var href = '';
				
				if (zenarioO.focus.items[i].cell_css_classes
				 && zenarioO.focus.items[i].cell_css_classes[c]
				 && ('' + zenarioO.focus.items[i].cell_css_classes[c]).indexOf('ghost') != -1) {
					//Don't allow ghosted Item Links to be clickable
					href = ' style="cursor: default;"';
					
					if (item_link == 'menu_item') {
						href += ' title="' + htmlspecialchars(item.name) + '|"';
					
					} else if (item_link == 'content_item' || item_link == 'content_item_or_url') {
						href += ' title="' + htmlspecialchars(item.name) + '|"';
					}
				
				} else {
					if (isSKLink && zenarioO.shallowLinks[item_link] && !window.zenarioONotFull) {
						href =
							' href="organizer.php#' + htmlspecialchars(ifNull(item.navigation_path, zenarioO.shallowLinks[item_link] + '//' + i)) + '" onclick="' +
								"zenarioO.deselectAllItems();" +
								"zenarioO.selectedItems['" + htmlspecialchars(i) + "'] = true;" +
								"zenarioO.saveSelection();" +
								"zenarioO.go('" + htmlspecialchars(zenarioO.shallowLinks[item_link]) + "', true, {id: '" + htmlspecialchars(value) + "', name: 'following_item_link', languageId: '" + htmlspecialchars(zenarioO.itemLanguage(i)) + "'}, undefined, undefined, undefined, undefined, '" + htmlspecialchars(value) + "');" +
								"return zenarioA.stop(event);" +
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
				
				
				var html = '<table class="organizer_no_border item_link"><tr>';
				
				if (item.css_class) {
					html += '<td><div class="listicon ' + item.css_class + '"><a' + href + '></a></div></td>';
				}
				
				html += '<td class="organizer_item_link_text" width="95%">';
				
				if (item_link == 'menu_item') {
					
					var longName = htmlspecialchars(item.name);
					var shortName = longName.replace(/.*?\-\&gt\; /g, '-&gt; ');
					
					if (shortName == longName) {
						shortName = longName.replace(/.*?\: /g, '');
					}
					
					html +=
						'<a' + href + '>' +
							shortName + 
						'</a>';
				
				} else {
					html +=
						'<a' + href + '>' +
							htmlspecialchars(item.name) + 
						'</a>';
				}
				
				html += '</td></tr></table>';
				
				return html;
			}
		}
	}
	
	
	//Otherwise check to see if this is a formated column
	if (zenarioO.focus.columns[c].format || zenarioO.focus.columns[c].empty_value) {
		value = zenarioA.formatSKItemField(value, zenarioO.focus.columns[c]);
	}
	
	
	var applyLengthLimit =
		zenarioO.focus.columns[c].length_limit
	 && value.length > zenarioO.focus.columns[c].length_limit;
	
	if (dontHTMLEscape) {
		if (applyLengthLimit) {
			return value.substr(0, zenarioO.focus.columns[c].length_limit) + '...';
		} else {
			return value;
		}
	
	} else {
		if (applyLengthLimit) {
			return '<span class="tooltip" title="' + htmlspecialchars(value) + '">' +
						htmlspecialchars(value.substr(0, zenarioO.focus.columns[c].length_limit)) +
					'...</span>';
		
		} else {
			return htmlspecialchars(value);
		}
	}
};

zenarioO.rowCssClass = function(i) {
	if (zenarioO.focus.items[i] && zenarioO.focus.items[i].row_css_class) {
		return ' ' + zenarioO.focus.items[i].row_css_class;
	} else {
		return '';
	}
};

zenarioO.columnCssClass = function(c, i) {
	var html = '';
	
	if (zenarioO.focus.columns[c] && zenarioO.focus.columns[c]['css_class']) {
		html += ' ' + zenarioO.focus.columns[c]['css_class'];
	}
	
	if (i != undefined
	 && zenarioO.focus.items[i]
	 && zenarioO.focus.items[i].cell_css_classes
	 && zenarioO.focus.items[i].cell_css_classes[c]) {
		html += ' ' + zenarioO.focus.items[i].cell_css_classes[c];
	}
	
	return html;
};




//	Display Functions  //

//Warning: the zenarioO.setBackButton() and zenarioO.getBackButtonTitle() functions both
//rely on the zenarioO.setTopLevelItems() function being called first!
zenarioO.setBackButton = function() {
	var i,
		html = '',
		titles = zenarioO.getBackButtonTitle(-1, true),
		data = {
			buttons: []
		};
	
	if (!titles || titles.length == 0) {
		get('organizer_backButton').style.visibility = 'hidden';
	
	} else {
		get('organizer_backButton').style.visibility = 'visible';
		
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
	var i, selectedItems, selectedItem = false;
	if (selectedItems = zenarioO.getFromLastPanel(zenarioO.branches.length-1, zenarioO.path, 'selected_items', true)) {
		if (selectedItems = selectedItems[path]) {
			foreach (selectedItems as var i) {
				selectedItem = i;
				break;
			}
		}
	}
	
	return selectedItem;
};

zenarioO.getFromLastPanel = function(branchLevel, path, thing, branchBelow, times, getArray, stats, secondCall) {
	
	if (!zenarioO.focus || !zenarioO.path) {
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
		 || (branchLevel === 0 && !zenarioO.focus.back_link)
			
			//Don't allow the back button if this is the panel for a second-level nav
		 || ((branchLevel <= 1)
		  && (hash = zenarioO.getHash(true, branchLevel))
		  && ((branchLevel === 0 && hash == zenarioO.currentTopLevelPath)
		   || (branchLevel === 1 && hash == zenarioO.currentTopLevelPath)))) {
			
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
			backLink = zenarioO.focus.back_link;
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
//rely on the zenarioO.setTopLevelItems() function being called first!
zenarioO.setTopLevelItems = function() {
	var i, j,
		ti = -1,
		selected1st,
		selected2nd,
		longestMatch = 0,
		data = {
			items: [],
			selected1st: 0
		};
		
	//Work out which top level item was selected, either by looking at the first branch, or if there are no
	//branches, the current path.
	var path = zenarioO.path,
		navPath = zenarioO.getHash(true);
	
	if (zenarioO.branches.length > 1) {
		path = zenarioO.branches[1].from;
	}
	
	
	//Loop through all of the first and second level navs
	foreach (zenarioO.map as i) {
		if (typeof zenarioO.map[i] == 'object' && zenarioO.map[i].nav) {
			foreach (zenarioO.map[i].nav as j) {
				if (typeof zenarioO.map[i].nav[j] == 'object') {
					
					//Work out which first/second level nav should be selected by comparing its path against the navigation path
					var thisNavPath = i + '/nav/' + j + '/panel';
					
					if (zenarioO.map[i].nav[j].link
					 && zenarioO.map[i].nav[j].link.path) {
						thisNavPath = zenarioO.map[i].nav[j].link.path;
					}
					
					var length = (thisNavPath + '').length + 1;
					
					if (longestMatch < length
					 && (navPath + '/').substr(0, length) == thisNavPath + '/') {
						longestMatch = length;
						selected1st = i;
						selected2nd = j;
					}
				}
			}
		}
	}
	
	
	
	//Loop through each top level nav
	foreach (zenarioO.sortedTopLevelItems as var itemNo) {
		i = zenarioO.sortedTopLevelItems[itemNo];
		
		if (zenarioA.hidden(zenarioO.map[i])) {
			continue;
		}
		
		var first2nd = true,
			si = -1,
			prop;
		
		//Set data to pass to the microtemplate
		data.items[++ti] = {
			id: i,
			items: [],
			selected: i === selected1st,
			css_class: zenarioO.map[i].css_class,
			label: ifNull(zenarioO.map[i].label, zenarioO.map[i].name),
			tooltip: zenarioO.map[i].tooltip
		};
		zenarioO.setDataAttributes(zenarioO.map[i], data.items[ti]);
		
		if (i === selected1st) {
			data.selected1st = ti;
		}
		
		//Sort the second level nav under this top level
		zenarioO.createSortArraySecondLevel('sortedSecondLevelItems', i);
		
		//Loop through each second level nav
		foreach (zenarioO.sortedSecondLevelItems as var jtemNo) {
			j = zenarioO.sortedSecondLevelItems[jtemNo];
			
			if (zenarioA.hidden(zenarioO.map[i].nav[j])) {
				continue;
			}
			
			var path;
			if (zenarioO.map[i].nav[j].link
			 && zenarioO.map[i].nav[j].link.path) {
				path = zenarioO.map[i].nav[j].link.path;
			} else {
				path = i + '/nav/' + j + '/panel';
			}
			
			if (i === selected1st && j === selected2nd) {
				zenarioO.currentTopLevelPath = path;
			}
			
			data.items[ti].items[++si] = {
				id: j,
				selected: i === selected1st && j === selected2nd,
				href: '#' + path,
				onclick: "zenarioO.topLevelClick('" + jsEscape(i) + "', '" + jsEscape(j) + "', " + engToBoolean(si == 0) + "); return false;",
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

zenarioO.checkButtonHidden = function(button) {
	//Check if this button is hidden
	if (zenarioA.hidden(button, true)) {
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
	if (button.hide_on_refiner && zenarioO.refiner) {
		if (typeof button.hide_on_refiner == 'object') {
			if (engToBoolean(button.hide_on_refiner[zenarioO.refiner.name])) {
				return true;
			}
		} else {
			if (zenarioO.refiner.name == button.hide_on_refiner) {
				return true;
			}
		}
	}
	
	//Don't show a <call_js_function> button for a function that does not exist
	if (button.call_js_function && !zenarioA.checkFunctionExists(button.call_js_function['function'], button.call_js_function.encapsulated_object)) {
		return true;
	}
	
	//In select mode, don't show a link to a panel that we can't reach due to a max-path restriction
	if (zenarioO.pathNotAllowed(button.link)) {
		return true;
	}
	
	return false;
};

zenarioO.setButtons = function(transition) {
	zenarioO.itemsSelected = 0;
	
	foreach (zenarioO.selectedItems as var id) {
		++zenarioO.itemsSelected;
	}
	
	if (!get('organizer_rightColumnOptions')) {
		return;
	}
	
	if (zenarioO.changingHierarchy) {
		$('#organizer_rightColumnOptions').hide();
		$('#organizer_rightColumnApplyCancel').show();
	} else {
		$('#organizer_rightColumnOptions').show();
		$('#organizer_rightColumnApplyCancel').hide();
		
		delete zenarioO.upload;
		delete zenarioO.uploadIsItemLevel;
		if (zenarioO.itemsSelected) {
			zenarioO.setItemButtons(transition);
		} else {
			zenarioO.setCollectionButtons(transition);
		}
		zenarioO.setUploads();
		zenarioO.setChooseButton();
	}
};

zenarioO.setQuickFilters = function() {
	
	var bi = -1,
		buttons = [],
		buttonsPos = {};
	
	foreach (zenarioO.sortedQuickFilterButtons as var itemNo) {
		var i = zenarioO.sortedQuickFilterButtons[itemNo],
			button = zenarioO.focus.quick_filter_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		
		buttons[++bi] = {
				id: i,
				css_class: ifNull(button.css_class, '', ''),
				label: button.label,
				parent: button.parent
			};
		buttonsPos[i] = bi;
		
		buttons[bi].tooltip = button.tooltip;
		
		buttons[bi].onclick = "zenarioO.toggleQuickFilter('" + jsEscape(i) + "'); return false;";
		
		buttons[bi].enabled = zenarioO.quickFilterEnabled(i);
	}
	
	//Add parent/child relationships
	var parent;
	foreach (buttonsPos as var i) {
		if (parent = zenarioO.focus.quick_filter_buttons[i].parent) {
			if ((bi = buttonsPos[parent]) !== undefined) {
				if (!buttons[bi].children) {
					buttons[bi].children = [];
					buttons[bi].css_class += ' organiser_button_with_children';
				}
				
				if (buttons[buttonsPos[i]].enabled
				 && !engToBoolean(buttons[buttonsPos[i]].remove_filter)) {
					buttons[bi].childEnabled = true;
				}
				
				buttons[bi].children.push(buttons[buttonsPos[i]]);
			}
		}
	}
	for (bi = buttons.length - 1; bi >= 0; --bi) {
		if (zenarioO.focus.quick_filter_buttons[buttons[bi].id].parent) {
			buttons.splice(bi, 1);
		}
	}
	
	if (buttons.length) {
		$('#organizer_quickFilter').show().html(zenarioA.microTemplate('zenario_organizer_quick_filter_button', buttons));
		zenarioA.tooltips('#organizer_quickFilter');
		zenarioA.tooltips('#organizer_quickFilter ul ul', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
		
	} else {
		$('#organizer_quickFilter').hide().html('');
	}
};

zenarioO.setCollectionButtons = function(transition) {
	
	var bi = -1,
		buttons = [],
		buttonsPos = {},
		disabled;
	
	foreach (zenarioO.sortedCollectionButtons as var itemNo) {
		var i = zenarioO.sortedCollectionButtons[itemNo],
			button = zenarioO.focus.collection_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		//Hide the export to csv button if there are no rows
		if (!zenarioO.searchMatches && (engToBoolean(button.do_csv_export) || engToBoolean(button.hide_when_no_search_results))) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button);
		
		
		buttons[++bi] = {
				id: i,
				css_class: ifNull(button.css_class, 'label_without_icon'),
				label: ifNull(button.label, button.name),
				parent: button.parent,
				disabled: disabled
			};
		buttonsPos[i] = bi;
		
		if (disabled && button.disabled_tooltip) {
			buttons[bi].tooltip = button.disabled_tooltip;
		} else {
			buttons[bi].tooltip = button.tooltip;
		}
		
		
		if (disabled) {
			buttons[bi].onclick = "return false;";
			
		} else if (button.navigation_path) {
			buttons[bi].href = '#/' + button.navigation_path;
		
		} else if (button.frontend_link) {
			buttons[bi].href = zenario.addBasePath(button.frontend_link);
		
		} else {
			buttons[bi].onclick = "zenarioO.collectionButtonClick('" + jsEscape(i) + "'); return false;";
		}
	}
	
	//Add parent/child relationships
	var parent;
	foreach (buttonsPos as var i) {
		if (parent = zenarioO.focus.collection_buttons[i].parent) {
			if ((bi = buttonsPos[parent]) !== undefined) {
				if (!buttons[bi].children) {
					buttons[bi].children = [];
					buttons[bi].css_class += ' organiser_button_with_children';
				}
				buttons[bi].children[buttons[bi].children.length] = buttons[buttonsPos[i]];
			}
		}
	}
	for (bi = buttons.length - 1; bi >= 0; --bi) {
		if (zenarioO.focus.collection_buttons[buttons[bi].id].parent) {
			buttons.splice(bi, 1);
		}
	}
	
	if (buttons.length) {
		zenarioO.showCollectionButtons(zenarioA.microTemplate('zenario_organizer_button', buttons), transition);
		zenarioA.tooltips('#organizer_collectionToolbarButtons');
		zenarioA.tooltips('#organizer_collectionToolbarButtons ul ul', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
		
	} else {
		zenarioO.hideCollectionButtons(transition);
	}
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



zenarioO.setItemButtons = function(transition) {
	
	var i,
		bi = -1,
		button,
		buttons = [],
		buttonsPos = {},
		parent,
		disabled;
	
	if (zenarioO.itemsSelected > 0) {
		foreach (zenarioO.sortedItemButtons as var itemNo) {
			i = zenarioO.sortedItemButtons[itemNo];
			button = zenarioO.focus.item_buttons[i];
			
			//Check if this button is hidden
			if (zenarioO.checkButtonHidden(button)) {
				continue;
			}
			
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
			
			if (!zenarioO.checkTraits(button, zenarioO.selectedItems)) {
				continue;
			}
			
			disabled = zenarioO.checkDisabled(button, zenarioO.selectedItems);
			
			
			buttons[++bi] = {
				id: i,
				css_class: ifNull(button.css_class, 'label_without_icon'),
				label: ifNull(button.label, button.name),
				parent: button.parent,
				disabled: disabled
			};
			buttonsPos[i] = bi;
			
			if (zenarioO.itemsSelected > 1 && button.multiple_select_label) {
				buttons[bi].label = button.multiple_select_label;
			}
			
			if (disabled && button.disabled_tooltip) {
				buttons[bi].tooltip = button.disabled_tooltip;
			
			} else if (zenarioO.itemsSelected > 1 && button.multiple_select_tooltip) {
				buttons[bi].tooltip = button.multiple_select_tooltip;
			
			} else {
				buttons[bi].tooltip = button.tooltip;
			}
			
			
			if (disabled) {
				buttons[bi].onclick = "return false;";
				
			} else if (button.navigation_path) {
				var id, item;
				if ((id = zenarioO.getKeyId(true)) && (item = zenarioO.focus.items[id]) && (item.navigation_path)) {
					buttons[bi].href = '#/' + item.navigation_path;
				} else {
					buttons[bi].href = '#/' + button.navigation_path;
				}
			
			} else if (button.frontend_link) {
				var id, item;
				if ((id = zenarioO.getKeyId(true)) && (item = zenarioO.focus.items[id]) && (item.frontend_link)) {
					buttons[bi].href = zenario.addBasePath(item.frontend_link);
				} else {
					buttons[bi].href = zenario.addBasePath(button.frontend_link);
				}
			
			} else {
				buttons[bi].onclick = "zenarioO.itemButtonClick('" + jsEscape(i) + "'); return false;";
				
				//Try to add a HTML 5 drag-and-drop uploader
				if (button.upload && engToBoolean(button.upload.drag_and_drop) && !zenarioO.upload) {
					zenarioO.upload = button.upload;
					zenarioO.uploadIsItemLevel = true;
				}
			}
		}
	}
	
	//Add parent/child relationships
	foreach (buttonsPos as var i) {
		if (parent = zenarioO.focus.item_buttons[i].parent) {
			if ((bi = buttonsPos[parent]) !== undefined) {
				if (!buttons[bi].children) {
					buttons[bi].children = [];
					buttons[bi].css_class += ' organiser_button_with_children';
				}
				buttons[bi].children[buttons[bi].children.length] = buttons[buttonsPos[i]];
			}
		}
	}
	for (bi = buttons.length - 1; bi >= 0; --bi) {
		button = zenarioO.focus.item_buttons[buttons[bi].id];
		if (button.parent || (engToBoolean(button.hide_when_children_are_not_visible) && !buttons[bi].children)) {
			buttons.splice(bi, 1);
		}
	}
	
	if (buttons.length) {
		zenarioO.showItemButtons(zenarioA.microTemplate('zenario_organizer_button', buttons), transition);
		zenarioA.tooltips('#organizer_itemToolbarButtons');
		zenarioA.tooltips('#organizer_itemToolbarButtons ul ul', {position: {my: 'left+2 center', at: 'right center', collision: 'flipfit'}});
		
	} else {
		zenarioO.hideItemButtons(transition);
	}
	
	zenarioO.setWrapperClass('multiple_items_selected', zenarioO.itemsSelected > 1);
};

//Check to see whether a button should be disabled
zenarioO.checkDisabled = function(button, items) {
	var id;
	
	if (engToBoolean(button.disabled)) {
		return true;
	}
	
	if (button.disabled_if !== undefined) {
		if (zenarioA.eval(button.disabled_if, button)) {
			return true;
		}
	}
	
	//Check whether an item button with the visible_if_for_all_selected_items/visible_if_for_any_selected_items
	//properties should be visible
	if (items && button.disabled_if_for_any_selected_items !== undefined) {
		foreach (items as id) {
			if (zenarioA.eval(button.disabled_if_for_any_selected_items, button, zenarioO.focus.items[id], id)) {
				return true;
			}
		}
	}
	
	if (items && button.disabled_if_for_all_selected_items !== undefined) {
		foreach (items as id) {
			if (!zenarioA.eval(button.disabled_if_for_all_selected_items, button, zenarioO.focus.items[id], id)) {
				return false;
			}
		}
		
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
			if (!zenarioA.eval(button.visible_if_for_all_selected_items, button, zenarioO.focus.items[id], id)) {
				return false;
			}
		}
	}
	
	met = false;
	if (button.visible_if_for_any_selected_items !== undefined) {
		foreach (items as id) {
			if (zenarioA.eval(button.visible_if_for_any_selected_items, button, zenarioO.focus.items[id], id)) {
				met = true;
				break;
			}
		}
		
		if (!met) {
			return false;
		}
	}
	
	
	//Old deprecated traits logic
	//Check if ths button requires a trait or a column to be set, or not to be set, in order to be shown.
	//Note that traits use an engToBoolean() check but columns just use a normal boolean check.
	//(i.e. 'No' or 'False' as strings would be true).
	met = true;
	var checks = {
			traits: {
				condition: function(id, trait) {
					return zenarioO.focus.items[id].traits && engToBoolean(zenarioO.focus.items[id].traits[trait]); 
				}
			},
			without_traits: {
				condition: function(id, trait) {
					return !zenarioO.focus.items[id].traits || !engToBoolean(zenarioO.focus.items[id].traits[trait]);
				}
			},
			with_columns_set: {
				condition: function(id, column) {
					return engToBoolean(zenarioO.focus.items[id][column]);
				}
			},
			without_columns_set: {
				condition: function(id, column) {
					return !engToBoolean(zenarioO.focus.items[id][column]);
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
					if (!zenarioO.isInfoTag(trait) && engToBoolean(button[check][trait])) {
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


zenarioO.setInlineButtons = function(id) {
	
	var bi = -1,
		buttons = [],
		buttonsPos = {},
		disabled;
	
	foreach (zenarioO.sortedInlineButtons as var itemNo) {
		var i = zenarioO.sortedInlineButtons[itemNo],
			button = zenarioO.focus.inline_buttons[i],
			ids = {};
		ids[id] = true;
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		if (!zenarioO.checkTraits(button, ids)) {
			continue;
		}
		
		disabled = zenarioO.checkDisabled(button, ids);
		
		
		buttons[++bi] = {
			id: i,
			css_class: ifNull(button.css_class, 'label_without_icon'),
			label: ifNull(button.label, button.name),
			parent: button.parent,
			disabled: disabled
		};
		buttonsPos[i] = bi;
		
		if (disabled && button.disabled_tooltip) {
			buttons[bi].tooltip = button.disabled_tooltip;
		} else {
			buttons[bi].tooltip = button.tooltip;
		}
		
		
		if (disabled) {
			buttons[bi].onclick = "return false;";
			
		} else if (button.navigation_path) {
			var item;
			if ((item = zenarioO.focus.items[id]) && (item.navigation_path)) {
				buttons[bi].href = '#/' + item.navigation_path;
			} else {
				buttons[bi].href = '#/' + button.navigation_path;
			}
		
		} else if (button.frontend_link) {
			var item;
			if ((item = zenarioO.focus.items[id]) && (item.frontend_link)) {
				buttons[bi].href = zenarioO.parseReturnLink(item.frontend_link);
			} else {
				buttons[bi].href = zenarioO.parseReturnLink(button.frontend_link);
			}
		
		} else {
			buttons[bi].onclick = "zenarioO.inlineButtonClick('" + jsEscape(i) + "', '" + jsEscape(id) + "'); return false;";
		}
	}
	
	if (buttons.length) {
		return buttons;
	} else {
		return undefined;
	}
};

zenarioO.setUploads = function(itemLevel) {
	
	foreach (zenarioO.sortedCollectionButtons as var itemNo) {
		var i = zenarioO.sortedCollectionButtons[itemNo],
			button = zenarioO.focus.collection_buttons[i];
		
		//Check if this button is hidden
		if (zenarioO.checkButtonHidden(button)) {
			continue;
		}
		
		//If this is an export csv button, remember that CSV exports are available
		if (engToBoolean(button.do_csv_export)) {
			zenarioO.CSVExportEnabled = true;
		}
		
		//Try to add a HTML 5 drag-and-drop uploader
		if (button.upload && engToBoolean(button.upload.drag_and_drop) && !zenarioO.upload) {
			zenarioO.upload = button.upload;
			zenarioO.uploadIsItemLevel = false;
		}
	}	
	
	if (zenarioO.upload && zenarioA.canDoHTML5Upload()) {
		var request = zenarioO.getKey(zenarioO.uploadIsItemLevel);
		
		if (zenarioO.upload.request) {
			foreach (zenarioO.upload.request as var k) {
				if (!zenarioO.isInfoTag(k)) {
					request[k] = zenarioO.upload.request[k];
				}
			}
		}
		
		request.__pluginClassName__ = zenarioO.upload.class_name;
		request.__path__ = zenarioO.path;
		request.method_call = 'handleOrganizerPanelAJAX';
		
		zenarioA.setHTML5UploadFromDragDrop(URLBasePath + 'zenario/ajax.php', request, zenarioO.selectCreatedIds, get('organizer_rightColumn'));
		
		$('#organizer_rightColumn').addClass('upload_enabled').removeClass('dragover');
	
	} else {
		zenarioO.clearUpload();
	}
};

zenarioO.clearUpload = function() {
	delete zenarioO.upload;
	delete zenarioO.uploadIsItemLevel;
	zenarioA.clearHTML5UploadFromDragDrop();
	$('#organizer_rightColumn').removeClass('upload_enabled').removeClass('dragover');
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
			className = 'submit_disabled';
		
		if (zenarioO.chooseButtonActive()
		 && zenarioO.checkTraits(window.zenarioOSelectObject, zenarioO.selectedItems)
		 && !zenarioO.checkDisabled(window.zenarioOSelectObject, zenarioO.selectedItems)
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


zenarioO.setPages = function() {
	
	if (!zenarioO.page) {
		return;
	}
	
	if (zenarioO.searchMatches !== undefined) {
		zenarioO.pageCount = Math.ceil(zenarioO.searchMatches / zenarioO.thisPageSize);
	} else {
		zenarioO.pageCount = 1;
	}
	
	get('organizer_pagePrev').style.display =
	get('organizer_pageNext').style.display =
	get('organizer_pagePrevOff').style.display =
	get('organizer_pageNextOff').style.display = 'none';
	
	if (zenarioO.pageCount == 1) {
		get('organizer_pages').innerHTML = '';
	} else {
		if (zenarioO.page > 1) {
			get('organizer_pagePrev').style.display = '';
		} else {
			get('organizer_pagePrevOff').style.display = '';
		}
		
		if (zenarioO.page < zenarioO.pageCount) {
			get('organizer_pageNext').style.display = '';
		} else {
			get('organizer_pageNextOff').style.display = '';
		}
		
		
		var positions1 = [
				-999999,
				-100000, -70000, -40000, -20000,
				-10000, -7000, -4000, -2000,
				-1000, -700, -400, -200,
				-100, -70, -40, -20,
				-10, -7, -4, -3, -2,
				-1, 0,
				1, 2, 3, 4, 7,
				10, 20, 40, 70,
				100, 200, 400, 700,
				1000, 2000, 4000, 7000,
				10000, 20000, 40000, 70000,
				100000,
				999999
			],
			positions2 = {};
		
		//Check if each is there, and include it if so
		foreach (positions1 as var i) {
			var rel = positions1[i];
			
			//Check if the set position is out of range, and replace it with the first/last page in range if needed
			var pos = zenarioO.page - 1 + rel;
			if (pos < 0) {
				pos = 0;
			} else if (pos >= zenarioO.pageCount) {
				pos = zenarioO.pageCount-1;
			} else {
				//Otherwise if the numbers are in range then round numbers, depending on how far away they are from the current page
				for (var round = 100000; round > 1; round /= 10) {
					if (rel < -round || round < rel) {
						pos = pos - ((zenarioO.page - 1) % round) - 1;
						break;
					}
				}
				
				if (pos < 0) {
					pos = 0;
				} else if (pos >= zenarioO.pageCount) {
					pos = zenarioO.pageCount-1;
				}
			}
			
			positions2[1*pos + 1] = true;
		}
		
		//Count how long the string is
		var len = '';
		foreach (positions2 as var p) {
			len += (len? ' ' : '') + p;
		}
		len = len.length;
		
		//Work out how much room for page numbers we have
		var html = '',
			max = Math.floor(($('#organizer_itemCountWrap').width() - $('#organizer_before_pages').width() - $('#organizer_after_pages').width()) / 10);
		
		//If there is barely any room, don't show pagination and just use the forward/back buttons
		max -= (' ' + zenarioO.page).length;
		max -= (' ' + zenarioO.pageCount).length;
		if (max > 0) {
			
			//If the pagination is too long, start cutting page numbers to fit it in
			len -= (' ' + zenarioO.page).length;
			len -= (' ' + zenarioO.pageCount).length;
			if (len > max) {
				
				var quota = max;
				foreach (positions2 as var p) {
					if (p != 1 && p != zenarioO.page && p != zenarioO.pageCount) {
						if (quota > max) {
							delete positions2[p];
						}
						quota = (quota + max) % len;
					}
				}
			}
			
			//Draw all of the chosen page numbers
			foreach (positions2 as var p) {
				html += '<a class="' + (p == zenarioO.page? 'currentPage' : 'page') + '" onclick="zenarioO.goToPage(' + p + ');">' + p + '</a> ';
			}
		}
		
		get('organizer_pages').innerHTML = html;
	}
};


zenarioO.getPanelTitle = function() {
	var title = zenarioO.focus.title;
	
	if (window.zenarioOSelectMode && (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)) {
		if (window.zenarioOMultipleSelect && zenarioO.focus.multiple_select_mode_title) {
			title = zenarioO.focus.multiple_select_mode_title;
		} else if (zenarioO.focus.select_mode_title) {
			title = zenarioO.focus.select_mode_title;
		}
	}
	
	return title;
};

zenarioO.setPanelTitle = function() {
	if (zenarioO.focus && get('organizer_rightColumnTitle')) {
		get('organizer_rightColumnTitle').style.display = '';
		
		var title = zenarioO.getPanelTitle();
		
		if (zenarioO.filteredView) {
			title += phrase.refined;
		}
		
		if (zenarioO.focus.item && zenarioO.focus.item.css_class) {
			get('organizer_panelTitle').innerHTML =
				'<div class="listicon ' + zenarioO.focus.item.css_class + '"></div><div>' + htmlspecialchars(title) + '</div>';
		} else {
			get('organizer_panelTitle').innerHTML = htmlspecialchars(title);
		}
		
		var width = Math.max(0, ifNull($('#organizer_rightColumnTitle').width(), 0, 0) - 75 - ifNull($('#organizer_collectionToolbar').width(), 90));
		
		//get('organizer_panelTitle').style.fontSize = Math.min(18, 1.6 * Math.round((width) / ('' + title).length)) + 'px';
		//$('#organizer_panelTitle').width(width);
		
		zenarioA.setTooltipIfTooLarge('#organizer_panelTitle', title, zenarioA.tooltipLengthThresholds.organizerPanelTitle);
		
		if (zenarioA.storekeeperWindow) {
			document.title = zenarioO.pageTitle + phrase.colon + title;
		}
	}
};


zenarioO.setTrash = function () {
	if (!window.zenarioOSelectMode && zenarioO.focus && zenarioO.focus.trash) {
		get('organizer_trash_button').style.display = '';
		get('organizer_trash_button').className =
			engToBoolean(zenarioO.focus.trash.empty)?
				ifNull(zenarioO.focus.trash.empty_css_class, 'trash_button_empty')
			 :	ifNull(zenarioO.focus.trash.full_css_class, 'trash_button_full');
		
		zenarioA.tooltips('#organizer_trash_button a', {
			position: {
				my: 'center bottom-12',
				at: 'center top',
				collision: 'flipfit',
				using: zenario.tooltipsUsing
			},
			items: '*',
			content:
				engToBoolean(zenarioO.focus.trash.empty)?
					ifNull(zenarioO.focus.trash.empty_tooltip, phrase.viewTrash)
				 :	ifNull(zenarioO.focus.trash.full_tooltip, phrase.viewTrash)
		});
		
	} else {
		get('organizer_trash_button').style.display = 'none';
	}
};

zenarioO.setPanelItems = function() {
	if (!get('organizer_rightColumnContent')) {
		return;
	}
	
	var html = '',
		itemsExist = false,
		c, colNo,
		ci = -1,
		ii = -1,
		lastCol = false,
		firstCell = 'firstcell ',
		columnWidths = {},
		numberOfInlineButtons = 0,
		labelColumns = {},
		data = {
			items: [],
			itemsById: {},
			columns: [],
			totalWidth: 0,
			maxNumberOfInlineButtons: 0,
			allItemsSelected: zenarioO.allItemsSelected(),
			reordering: zenarioO.reorderingUsingOldMethod? 'organizer_reordering' : '',
			slidedown_view_microtemplate: zenarioO.focus.slidedown_view_microtemplate
		};
	
	foreach (zenarioO.sortedColumns as colNo => c) {
		if (zenarioO.isShowableColumn(c, true)) {
			lastCol = c;
		}
	}
	foreach (zenarioO.sortedColumns as colNo => c) {
		
		if (zenarioO.labelFormat.indexOf('[[' + c + ']]') !== -1) {
			labelColumns[c] = true;
		}
		
		if (zenarioO.isShowableColumn(c, true)) {
			
			if (zenarioO.prefs[zenarioO.voPath]
			 && zenarioO.prefs[zenarioO.voPath].colSizes
			 && zenarioO.prefs[zenarioO.voPath].colSizes[c]) {
				columnWidths[c] = Math.max(zenarioO.prefs[zenarioO.voPath].colSizes[c], zenarioO.columnWidths.xxsmall);
			} else {
				columnWidths[c] = zenarioO.focus.columns[c].width;
			}
			
			if (columnWidths[c] && zenarioO.columnWidths[columnWidths[c]]) {
				columnWidths[c] = zenarioO.columnWidths[columnWidths[c]];
			
			} else if (!(columnWidths[c] = 1*columnWidths[c])) {
				columnWidths[c] = zenarioO.defaultColumnWidth
			}
			
			if (data.totalWidth) {
				data.totalWidth += zenarioO.columnSpacing;
			}
			data.totalWidth += columnWidths[c] + zenarioO.columnPadding;
			
			data.columns[++ci] = {
				id: c,
				htmlId: 'organizer_column__' + c,
				css_class:
					firstCell + (lastCol == c? 'lastcell' : '') +
					(labelColumns[c]? ' label_column' : '') +
					zenarioO.columnCssClass(c),
				title: zenarioO.focus.columns[c].title,
				tooltip: zenarioO.focus.columns[c].tooltip,
				width: columnWidths[c]
			};
			firstCell = '';
		}
	}
	data.totalWidth += zenarioO.columnsExtraSpacing;
	
	zenarioO.shownItems = {};
	zenarioO.shownItemsLength = 0;
	if (zenarioO.focus.items) {
		//Work out which items to display for this page,
		var pageStop = zenarioO.page * zenarioO.thisPageSize,
			pageStart = pageStop - zenarioO.thisPageSize;
		pageStop = Math.min(pageStop, zenarioO.searchMatches);
		
		get('organizer_pageStart').innerHTML = pageStart + 1;
		get('organizer_pageStop').innerHTML = pageStop;
		
		var firstRow = 'firstrow ';
		foreach (zenarioO.searchedItems as var itemNo) {
			if (!zenarioO.isInfoTag(itemNo)) {
				itemsExist = true;
				var i = zenarioO.searchedItems[itemNo];
				
				if (!zenarioO.focus.__item_hierarchy__) {
					if (itemNo >= pageStop) {
						break;
					} else if (itemNo < pageStart) {
						continue;
					}
				}
				
				if (!zenarioO.focus.items[i]) {
					continue;
				}
				
				var parentId = false;
				if (zenarioO.itemHierarchy[i]) {
					parentId = zenarioO.itemHierarchy[i];
				}
				
				data.items[++ii] = {
					id: i,	//Using "[[id]]" in your microtemplates should both html and js escape this
					parentId: parentId,
					cells: [],
					slidedown_view_open: zenarioO.inCloseUpView && i == zenarioO.itemToSelect,
					slidedown_view_microtemplate: zenarioO.focus.slidedown_view_microtemplate,
					grid_view_microtemplate: zenarioO.focus.grid_view_microtemplate,
					canDoubleClick: !!zenarioO.itemDoubleClickLink(i),
					canDrag: zenarioO.changingHierarchyView && !engToBoolean(zenarioO.focus.items[i].disable_reorder),
					label: zenarioO.applySmallSpaces($.trim(zenarioO.applyMergeFields(zenarioO.labelFormat, false, i, true))).replace(/\n/g, '<br/>'),
					selected: !!zenarioO.selectedItems[i],
					css_class: zenarioO.rowCssClass(i) + ' ' + firstRow
				};
				
				data.items[ii].image_css_class = zenarioO.getItemCSSClass(i);
				
				if (zenarioO.view_mode != 'grid' && zenarioO.focus.items[i].list_image) {
					data.items[ii].image_css_class += ' organiser_item_with_image';
					data.items[ii].image = zenario.addBasePath(zenarioO.focus.items[i].list_image);
				
				} else if (zenarioO.view_mode == 'grid' && zenarioO.focus.items[i].image) {
					data.items[ii].image_css_class += ' organiser_item_with_image';
					data.items[ii].image = zenario.addBasePath(zenarioO.focus.items[i].image);
				}
				
				data.items[ii].tooltip = zenarioO.focus.items[i].tooltip;
				if (!data.items[ii].tooltip && zenarioO.focus.item) {
					if (zenarioO.focus.item.tooltip_when_link_is_active && zenarioO.itemDoubleClickLink(i)) {
						data.items[ii].tooltip = zenarioO.applyMergeFields(zenarioO.focus.item.tooltip_when_link_is_active, true, i);
					} else if (zenarioO.focus.item.tooltip) {
						data.items[ii].tooltip = zenarioO.applyMergeFields(zenarioO.focus.item.tooltip, true, i);
					}
				}
				
				var ei = -1,
					firstCell = 'firstcell ',
					needsComma = false,
					lastNeedsComma = false,
					value;
				foreach (zenarioO.sortedColumns as colNo => c) {
					if (zenarioO.isShowableColumn(c, true)) {
						
						value = zenarioO.columnValue(i, c);
						
						//Put commas between words, but don't put commas between non-words or words that end with something.
						needsComma = !(value == '' && value !== 0);
						
						data.items[ii].cells[++ei] = {
							id: c,
							first: !!firstCell,
							css_class:
								'organizer_column__' + c + '__cell ' +
								firstRow + firstCell + (lastCol == c? 'lastcell' : '') +
								(engToBoolean(zenarioO.focus.columns[c].align_right)? ' right' : '') +
								(labelColumns[c]? ' label_column' : '') + 
								zenarioO.columnCssClass(c, i),
							value: value,
							needsComma: needsComma && lastNeedsComma,
							width: columnWidths[c]
						};
						
						firstCell = '';
						lastNeedsComma = needsComma && !(value + '').match(/\W\s*$/);
					}
				}
				
				
				data.items[ii].inline_buttons = zenarioO.setInlineButtons(i);
				
				if (data.items[ii].inline_buttons
				 && (numberOfInlineButtons = data.items[ii].inline_buttons.length)
				 && (numberOfInlineButtons > data.maxNumberOfInlineButtons)) {
					data.maxNumberOfInlineButtons = numberOfInlineButtons;
				}
				
				data.itemsById[i] = data.items[ii];
				
				firstRow = '';
				zenarioO.shownItems[i] = true;
				++zenarioO.shownItemsLength;
			}
		}
		
		foreach (data.items as ii) {
			data.items[ii].maxNumberOfInlineButtons = data.maxNumberOfInlineButtons;
		}
	}
	
	foreach (zenarioO.selectedItems as var i) {
		//Remove any items that have disappeared from view
		if (!zenarioO.shownItems[i]) {
			delete zenarioO.selectedItems[i];
		}
	}
	
	
	if (!itemsExist) {
		//Display a message if there were no items to display
		if (zenarioO.filteredView) {
			data.no_items_message = zenarioO.focus.no_items_in_search_message? zenarioO.focus.no_items_in_search_message : phrase.noItemsInSearch;
		} else {
			data.no_items_message = zenarioO.focus.no_items_message? zenarioO.focus.no_items_message : phrase.noItems;
		}
	}
	
	if (zenarioO.view_mode == 'grid') {
		html = zenarioA.microTemplate('zenario_organizer_grid', data);
		
	} else if (zenarioO.view_mode == 'hierarchy') {
		html = zenarioA.microTemplate('zenario_organizer_hierarchy', data);
		
	} else {
		html = zenarioA.microTemplate('zenario_organizer_list', data);
	}
	
	
	get('organizer_rightColumnContent').innerHTML = html;
	get('organizer_rightColumnContent').className = zenarioO.view_mode + '_view';
	
	if (zenarioO.view_mode == 'hierarchy') {
		var id,
			progress = true,
			parentIdsToOpen = {},
			$li,
			$dd = $('#organizer_rightColumnContent .dd'),
			maxDepth = 99;
		
		if (zenarioO.focus.reorder
		 && zenarioO.focus.reorder.hierarchy_depth) {
			maxDepth = zenarioO.focus.reorder.hierarchy_depth;
		}
		
		$dd.nestable({
			maxDepth: maxDepth
		});
		
		//If there was a search term, show all of the matches
		if (zenarioO.searchTerm === undefined) {
			//If there was no search term, start with everything closed
			$dd.nestable('collapseAll');
			
			//If this is a first load and opened up with an item, open it initially
			if (zenarioO.focus.__open_item_in_hierarchy__) {
				parentIdsToOpen[zenarioO.focus.__open_item_in_hierarchy__] = true;
			}
			
			//If this was a refresh, see what was open last time and make sure it is open this time
			if (zenarioO.openItemsInHierarchy) {
				foreach (zenarioO.openItemsInHierarchy as id) {
					parentIdsToOpen[id] = true;
				}
			}
			
			//If an item is selected, expand to it
			foreach (zenarioO.selectedItems as id) {
				if (zenarioO.itemHierarchy[id]) {
					parentIdsToOpen[zenarioO.itemHierarchy[id]] = true;
				}
			}
			//Also expand any of their parents, and their parents parents, and so on...
			while (progress) {
				progress = false;
				foreach (parentIdsToOpen as id) {
					if (zenarioO.itemHierarchy[id]
					 && !parentIdsToOpen[zenarioO.itemHierarchy[id]]) {
						parentIdsToOpen[zenarioO.itemHierarchy[id]] = true;
						progress = true;
					}
				}
			}
			foreach (parentIdsToOpen as id) {
				$li = $(get('organizer_item_' + id)).parent();
				if ($li.length) {
					$dd.nestable('expandItem', $li);
				}
			}
		}
		
		if (zenarioO.changingHierarchyView) {
			
			var scanNewHier = function(doSave) {
				var newHierArray = $dd.nestable('serialize_zenario_modified_version'),
					id,
					parentId,
					ordinal,
					changesMade = false,
					changes = {},
					newHier = {};
				
				//Loop through the levels of the new hierarchy (which will be structured as parentId -> id)
				foreach (newHierArray as parentId) {
					var changesThisLevel = false,
						ordinalsNotNormalised = false,
						length = newHierArray[parentId].length;
					
					//Loop through each item on this level, in the order they are in
					for (ordinal = 0; ordinal < length; ++ordinal) {
						id = newHierArray[parentId][ordinal];
						newHier[id] = parentId;
						
						if (!zenarioO.reorderingUsingNewMethod) {
							//If we're not reordering, only flag a change if we see an item with a new parent id
							if (parentId != (zenarioO.itemHierarchy[id] !== undefined? zenarioO.itemHierarchy[id] : 0)) {
								changes[id] = {parentId: parentId};
								changesMade = changesThisLevel = true;
							}
						} else {
							//If we're reordering as well, mark anything that's not in exactly its original place as a change
							if (!zenarioO.itemHierarchyArray
							 || !zenarioO.itemHierarchyArray[parentId]
							 || zenarioO.itemHierarchyArray[parentId][ordinal] === undefined
							 || zenarioO.itemHierarchyArray[parentId][ordinal] != id) {
								changes[id] = {parentId: parentId, ordinal: ordinal + 1};
								changesMade = changesThisLevel = true;
							}
						}
					}
					
					if (zenarioO.reorderingUsingNewMethod && changesThisLevel) {
						//If we're reordering, and there were changes, check all unchanged items on this level
						//to see if all of their ordinals are normalised
						for (ordinal = 0; ordinal < length; ++ordinal) {
							id = newHierArray[parentId][ordinal];
							if (!changes[id] && (ordinal + 1 != zenarioO.focus.items[id][zenarioO.focus.reorder.column])) {
								ordinalsNotNormalised = true;
								break;
							}
						}
						
						//If not, mark everything on this level as changed and normalise all of the ordinals
						if (ordinalsNotNormalised) {
							for (ordinal = 0; ordinal < length; ++ordinal) {
								id = newHierArray[parentId][ordinal];
								changes[id] = {parentId: parentId, ordinal: ordinal + 1};
							}
						}
					}
				}
				
				if (!doSave) {
					return changesMade;
				
				} else if (changesMade) {
					//Send an AJAX request to update the parent ids/ordinals
					var actionRequests = zenarioO.getKey(),
						saves = '';
					
					actionRequests.hierarchy = true;
						
					foreach (changes as id) {
						saves += (saves? ',' : '') + id;
						actionRequests['parent_id__' + id] = changes[id].parentId;
						
						if (zenarioO.reorderingUsingNewMethod) {
							actionRequests.reorder = true;
							actionRequests['ordinal__' + id] = changes[id].ordinal;
							
							//Backwards compatability
							actionRequests['item__' + id] = changes[id].ordinal;
							
							//Update the current data
							zenarioO.focus.items[id][zenarioO.focus.reorder.column] = changes[id].ordinal
						}
					}
					actionRequests.id = saves;
					
					//Send these results via AJAX
					var actionTarget =
						'zenario/ajax.php?' +
							'__pluginClassName__=' + zenarioO.focus.class_name +
							'&__path__=' + zenarioO.path +
							'&method_call=handleOrganizerPanelAJAX';
					
					//Clear the local storage, as there have probably just been changes
					delete zenario.rev;
					
					//Save the data
					var message = zenario.nonAsyncAJAX(
						URLBasePath + actionTarget,
						actionRequests
					);
					
					if (message) {
						zenarioA.showMessage(message);
					}
					zenarioO.refresh();
				}
			};
			
			$dd.on('change', function() {
				if (zenarioO.changingHierarchy = scanNewHier(false)) {
					$('#organizer_applyButton')
						.unbind()
						.click(function() {
							scanNewHier(true);
						});
				}
				zenarioO.setWrapperClass('changing_hierarchy', zenarioO.changingHierarchy);
				zenarioO.setButtons();
			});
		
		}
		
		$dd.on('collapseItem', function() {
			var changes = false;
			zenarioO.openItemsInHierarchy = {};
		
			$('.organizer_row[data-id]:visible').each(function(i, el) {
				zenarioO.openItemsInHierarchy[$(el).data('id')] = true;
			});
			
			//Check to see if any selected items were just hidden
			//If so, we need to deselect them
			foreach (zenarioO.selectedItems as var itemId) {
				if (zenarioO.selectedItems.hasOwnProperty(itemId)) {
					if (!zenarioO.openItemsInHierarchy[itemId]) {
						delete zenarioO.selectedItems[itemId];
						changes = true;
					}
				}
			}
			
			if (changes) {
				zenarioO.setButtons(true);
				zenarioO.saveSelection();
			}
		});
			
		$dd.on('expandItem', function() {
			/*zenarioO.openItemsInHierarchy = {};
		
			$('.organizer_row[data-id]:visible').each(function(i, el) {
				zenarioO.openItemsInHierarchy[$(el).data('id')] = true;
			});*/
			
			if (zenarioO.focus.reorder
			 && zenarioO.focus.reorder.lazy_load) {
				zenario.actAfterDelayIfNotSuperseded('zenario_hierarchy_expand', function() {
					if (zenarioA.uploading || zenarioO.changingHierarchy) {
						return false;
					}
					zenarioO.saveSelection();
					zenarioO.refresh();
				}, 1);
			}
		});
	}
	
	//Old code - selective tooltips
	/*
	if (zenarioO.view_mode == 'grid') {
		zenarioA.tooltips('#organizer_rightColumnContent div.item .organizer_image_container[title]');
	} else {
		zenarioA.tooltips('#organizer_rightColumnContent .listicon div[title]');
	}
	zenarioA.tooltips('#organizer_rightColumnContent span.tooltip[title]');
	zenarioA.tooltips('#organizer_rightColumnContent a[title]');
	*/
	
	//New code - why can't I just add tooltips wherever they might be needed?
	zenarioA.tooltips('#organizer_rightColumnContent [title]');
	
	
	var $sortAndResizeWrapper = $('#organizer_rightColumnContent .organizer_sort_and_resize_wrapper'),
		$sortables = $('#organizer_rightColumnContent .organizer_sort_and_resize_wrapper .organizer_sortable'),
		$resizables = $('#organizer_rightColumnContent .organizer_sort_and_resize_wrapper .organizer_resizable'),
		sortAndResizeable$Cols = {},
		sortableColOrigins = {},
		sizeListViewColumns = function() {
			var thisWidth,
				width = $('#organizer_non_sortable_resizeable_cols').width();
			
			$resizables.each(function(i, el) {
				if (thisWidth = $(el).outerWidth()) {
					width += thisWidth;
				}
			});
			
			$('#organizer_list_view').css('min-width', width + 'px');
			$('#organizer_list_view .organizer_row').css('min-width', width + 'px');
		},
		sortHandler = function(event, ui) {
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
		},
		resizeHandler = function(event, ui, save) {
			var id = $(ui.element).attr('id');
			if (id) {
				if (!sortAndResizeable$Cols[id]) {
					sortAndResizeable$Cols[id] = $('.' + id + '__cell');
				}
				
				if (save) {
					zenarioO.resizeColumn(id.replace('organizer_column__', ''), ui.element.width());
				} else {
					$sortAndResizeWrapper.css('width', 'auto');
					sortAndResizeable$Cols[id].width(ui.element.width());
					sizeListViewColumns();
				}
			}
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
		}
	});
	
	sizeListViewColumns();
	
	$sortables.each(function(i, el) {
		if (el.id) {
			sortableColOrigins[el.id] = 1*$(el).position().left;
		}
	});
};

zenarioO.toggleSlidedownView = function(i, e) {
	zenarioA.stop(e);
	
	if (zenarioO.inCloseUpView && i == zenarioO.itemToSelect) {
		zenarioO.closeSlidedownView(i);
	} else {
		zenarioO.selectSingleItem(i, true);
		zenarioO.openSlidedownView(i);
		zenarioO.setButtons(true);
		zenarioO.saveSelection();
	}
	
	zenarioO.setHash();
};

zenarioO.openSlidedownView = function(i) {
	if (zenarioO.inCloseUpView && zenarioO.itemToSelect) {
		zenarioO.closeSlidedownView(zenarioO.itemToSelect);
	}
	
	var $toggle = $(get('organizer_slidedown_view_toggle_' + i)),
		$slide = $(get('organizer_slidedown_' + i));
	
	if (!$slide.is(':visible')) {
		$toggle
			.removeClass('organizer_slidedown_view_toggle_closed').addClass('organizer_slidedown_view_toggle_open');
		
		$slide
			.clearQueue()
			.removeClass('organizer_slidedown_view_closed').addClass('organizer_slidedown_view_open')
			.html(zenarioA.microTemplate(zenarioO.focus.slidedown_view_microtemplate, {id: i}))
			.slideDown();
	}
	
	zenarioO.inCloseUpView = true;
	zenarioO.itemToSelect = i;
};

zenarioO.closeSlidedownView = function(i) {
	
	if (i === undefined) {
		if (zenarioO.inCloseUpView && zenarioO.itemToSelect) {
			i = zenarioO.itemToSelect;
		} else {
			return;
		}
	}
	
	var $toggle = $(get('organizer_slidedown_view_toggle_' + i)),
		$slide = $(get('organizer_slidedown_' + i));
		
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
	
	zenarioO.inCloseUpView = false;
};

zenarioO.getItemCSSClass = function (i) {
	if (zenarioO.focus.items[i].css_class) {
		return zenarioO.focus.items[i].css_class;
	} else if (zenarioO.focus.item) {
		return zenarioO.focus.item.css_class;
	} else {
		return undefined;
	}
};

zenarioO.setItemCount = function() {
	var count = 0;
	
	if (zenarioO.searchMatches !== undefined) {
		count = zenarioO.searchMatches;
	}
	
	if (count) {
		get('organizer_itemCount').innerHTML = htmlspecialchars(count);
		get('organizer_itemCountWrap').style.visibility = 'visible';
	} else {
		zenarioO.hideItemCount();
	}
};

zenarioO.hideItemCount = function() {
	get('organizer_itemCount').innerHTML = '';
	get('organizer_itemCountWrap').style.visibility = 'hidden';
};

//Send a signal to any Modules on the page to fill the lower-left of Organizer
//This should return an array of arrays of things in the format:
	//[[[html, ord], [html, ord], ... ], ... ]
//Flatten this, sort this, then output this.
zenarioO.fillLowerLeft = function() {
	var i, j,
		a2 = [],
		a = zenario.sendSignal('fillStorekeeperLowerLeft');
	
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
	
	if (zenarioA.storekeeperWindow) {
		window.parent.zenarioA.closeBox('AdminOrganizer', true);
		zenarioO.go(zenarioO.defaultPathInIframePreload);
		
		zenarioA.showAJAXLoader();
		zenarioO.firstLoaded = false;
	
	} else {
		zenarioO.go(zenarioO.defaultPathInIframePreload);
		zenarioA.closeBox('sk');
		zenarioO.firstLoaded = false;
	}
	
	return false;
};

zenarioO.choose = function() {
	//Stop admins making choices on outdated panels
	if (zenarioO.lastSuccessfulGoNum != zenarioO.goNum) {
		return;
	}
	
	if (window.parent
	 && window.zenarioOSelectMode
	 && (zenarioO.path == window.zenarioOTargetPath || window.zenarioOTargetPath === false)
	 && (zenarioO.itemsSelected > 0 || window.zenarioOAllowNoSelection)
	 && (zenarioO.itemsSelected <= 1 || window.zenarioOMultipleSelect)) {
		
		//Disable the current panel/page from refreshing
		zenarioO.stopRefreshing();
		
		var key = zenarioO.getKey(true),
			path = zenarioO.path,
			panel = $.extend(true, {}, zenarioO.focus),
			row;
		
		key._items = {};
		foreach (zenarioO.selectedItems as var i) {
			if (!zenarioO.isInfoTag(i)) {
				key._items[i] = true;
			}
			if (!window.zenarioOMultipleSelect) {
				break;
			}
		}
		
		if (!window.zenarioOMultipleSelect && zenarioO.selectedItems && key.id) {
			row = panel.items[key.id];
			row.__label_tag__ = zenarioO.defaultSortColumn;
		} else {
			row = {};
		}
		
		zenarioO.closeSelectMode();
		
		if (window.zenarioOMultipleSelect) {
			window.parent[window.zenarioOCallbackObject][window.zenarioOCallbackFunction](path, key, undefined, panel);
		} else {
			window.parent[window.zenarioOCallbackObject][window.zenarioOCallbackFunction](path, key, row, panel);
		}
	}
};



//
//	Some specific functions
//

//Edit the settings or view the Layers on the "Slots on the Content Item"
//These buttons needs specific requests that must be set up, and this function will add them
zenarioO.setupSlotAdminBox = function(object, id) {
	object = $.extend(true, {}, object);
	
	delete object.call_js_function;
	object.admin_box = object.custom_admin_box;
	
	if (!object.admin_box.key) {
		object.admin_box.key = {};
	}
	
	object.admin_box.key.slotName = id;
	object.admin_box.key.moduleId = zenarioO.focus.items[id].module_id;
	object.admin_box.key.instanceId = zenarioO.focus.items[id].instance_id;
	
	zenarioA.action(zenarioO, object, true);
};


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
	var ms = (new Date()).getTime();
	if (zenarioO.yourWorkInProgressLastUpdated - ms < -zenarioO.yourWorkInProgressLastUpdateFrequency) {
		zenarioO.yourWorkInProgressLastUpdated = ms;
		
		var dropdown = get('zenario_ywip_dropdown');
		if (dropdown) {
			$(dropdown).removeClass('zenario_ywip_loaded').addClass('zenario_ywip_loading');
			
			//Don't run syncronously
			setTimeout(function() {
				var url =
						URLBasePath + 'zenario/admin/ajax.php' +
						'?_json=1' +
						'&path=zenario__content/nav/content/panel' +
						'&_sort_col=last_modified_datetime' +
						'&_sort_desc=1' +
						'&_start=0' +
						'&_limit=' + zenarioO.yourWorkInProgressItemCount +
						'&refinerId=' +
						'&refinerName=your_work_in_progress' +
						'&refiner__your_work_in_progress=' +
						'&_get_item_data=1',
					WiP = zenario.nonAsyncAJAX(url, false, true, true),
					html = zenarioA.microTemplate('zenario_ywip', WiP);
				
				dropdown.innerHTML = html;
				$(dropdown).removeClass('zenario_ywip_loading').addClass('zenario_ywip_loaded');
			}, 1);
		}
	}
};
$(document).ready(zenarioO.updateYourWorkInProgress);





/*

//Code for shortcut keys in Storekeeper (currently removed and not implemented)

zenarioO.onKeyDown = function(e) {
	
	if (!(e = (e || event))) {
		return true;
	}
	
	if (!e.keyCode || zenarioA.floatingBoxOpen || zenarioO.searchFocused) {
		return true;
	}
	
	if (zenarioAB.isOpen) {
		if (e.keyCode == 27) {
			zenarioAB.closeButton(true);
			return false;
		} else {
			return true;
		}
	}
	
	foreach (zenarioA.boxesOpen as var n) {
		return true;
	}
	
	
	var bclass, modifier = e.ctrlKey || e.metaKey;
	
	switch (e.keyCode) {
		//escape
		case 27:
			zenarioO.deselectAllItems();
			zenarioO.setButtons(true);
			zenarioO.saveSelection();
			return true;
		
		//a
		case 65:
			bclass = ['select_all'];
			break;
		
		//c, d
		case 67:
		case 68:
			if (modifier) return true;
			bclass = ['duplicate_content'];
			break;
		
		//e, f
		case 69:
		case 70:
			if ($('#organizer_search_term').is(':visible')) {
				get('organizer_search_term').focus();
			}
			return false;
		
		//g, j
		case 71:
		case 74:
			if (modifier) return true;
			if (get('organizer_viewContentStorekeeper').onclick) {
				get('organizer_viewContentStorekeeper').onclick();
				return false;
			} else {
				bclass = ['view_fancybox', 'jump_to', 'viewInstances', 'zenario_trans_view_chained'];
			}
			break;
		
		//o
		case 79:
			bclass = ['settings', 'rename'];
			break;
		
		//r
		case 82:
			if (modifier) return true;
			zenarioO.refresh();
			return false;
		
		//v
		case 86:
			if (modifier) return true;
			if (get('organizer_viewContentFrontend').href) {
				zenario.goToURL(get('organizer_viewContentFrontend').href);
				return false;
			} else {
				bclass = ['view_content'];
			}
			break;
		
		//Add
		case 107:
			if (modifier) return true;
			bclass = ['add', 'zenario_trans_declare', 'add_to_menu', 'add_egg', 'add_tab'];
			break;
		
		//delete
		case 46:
			bclass = ['delete', 'trash', 'suspend'];
			break;
		
		//minus
		case 109:
			if (modifier) return true;
			bclass = ['minus', 'zenario_trans_remove', 'suspend'];
			break;
		
		//star
		case 56:
			if (!e.shiftKey) {
				return true;
			}
		case 106:
			bclass = ['create', 'create_multiple', 'create_image'];
			break;
		
		//Backspace
		case 8:
			if ($('#organizer_backButton').is(':visible')) {
				zenarioO.back();
			}
			return false;
		
		//Enter
		case 13:
			var id;
			if (id = zenarioO.getKeyId()) && (id = get('organizer_item_' + id)) {
				zenarioO.itemDoubleClick(id);
				return false;
			}
			break;
		
		//Page Up
		case 33:
			zenarioO.prevPage();
			return false;
		
		//Page Down
		case 34:
			zenarioO.nextPage();
			return false;
		
		//End
		case 35:
			zenarioO.arrowKey(e, 'last');
			return false;
		
		//Home
		case 36:
			zenarioO.arrowKey(e, 'first');
			return false;
		
		//Left/Up
		case 37:
		case 38:
			if (modifier) return true;
			zenarioO.arrowKey(e, 'up');
			return false;
			break;
		
		//Right/Down
		case 39:
		case 40:
			if (modifier) return true;
			zenarioO.arrowKey(e, 'down');
			return false;
			break;
		
		default:
			return true;
	}
	
	zenarioO.onKeyUpFoundButton = false;
	foreach (bclass as var b) {
		$('.toolbar .toolbarButtons .' + bclass[b]).each(function(i, el) {
			if (!zenarioO.onKeyUpFoundButton && $(el).is(':visible')) {
				$(el).click();
				zenarioO.onKeyUpFoundButton = true;
				return false;
			}
		});
	}
	
	return true;
	
	
	
};
*/


	/*
	
8 - backspace
9 - tab
224 - cmd
18 - alt
17 - ctrl
16 - shift
27 - escape
46 - delete
32 - space
13 - enter

33 - pageup
34 - pagedown
35 - end
36 - home

37 - left
38 - up
39 - right
40 - down

48,96 - 0
49,97 - 1
51,99 - 3
57,105 - 9

65 - a
66 - b
89 - y
90 - z

190,110 - .

109 - -
107 - +
106 (or shift + 56) - *
61 - =
	
	*/


/*

zenarioO.arrowKey = function(e, direction) {
	
	var previousItem = undefined,
		itemToSelect = undefined,
		useNextItem = false,
		useThisItem = false,
		itemsSelected = zenarioO.getKeyId(true);
	
	foreach (zenarioO.searchedItems as var itemNo) {
		if (!zenarioO.isInfoTag(itemNo)) {
			
			var currentItem = zenarioO.searchedItems[itemNo];
			if (zenarioO.shownItems && zenarioO.shownItems[currentItem]) {
				
				if (useThisItem) {
					itemToSelect = currentItem;
				}
				
				if (direction == 'first') {
					itemToSelect = currentItem;
					break;
				
				} else if (!itemsSelected) {
					if (direction == 'down') {
						itemToSelect = currentItem;
						break;
					}
				
				} else if (zenarioO.selectedItems[currentItem]) {
					if (direction == 'down') {
						useNextItem = true;
					
					} else if (direction == 'up' && previousItem !== undefined) {
						itemToSelect = previousItem;
						break;
					}
				}
				
				useThisItem = useNextItem;
				useNextItem = false;
				previousItem = currentItem;
			}
		}
	}
	
	if (!itemToSelect && direction == 'last' && previousItem !== undefined) {
		itemToSelect = previousItem;
	}
	
	if (itemToSelect && zenarioO.scrollToItem(itemToSelect, 300)) {
		zenarioO.itemClick(itemToSelect, e);
	}
};
*/


//Automatically set the box to the correct height for the users screen, or the maximum height requested, whichever is smaller
zenarioO.sizing = false;
zenarioO.lastSize = false;
zenarioO.size = function(refresh) {
			
	var outerGap = 46,
		colGap = 46,
		contentGap = 40,
		graphicGap = 66,
		titleGap = 0,
		buttonsGap = 30,
		bordersWidth = 2,
		rightColumnContentBorderWidth = 2,
		headerHeight = 35,
		headerToolbar = 55,
		leftColAdjustment,
		$zenario_fbsk = $('#zenario_fbsk');
	
	
	if (zenarioO.sizing) {
		clearTimeout(zenarioO.sizing);
	}
	
	if (!get('organizer__box_wrap')) {
		return;
	}
	
	//Try to get the width/height of the window
	var width, height;
	
	if (!zenarioA.storekeeperWindow) {
		width = Math.floor($zenario_fbsk.width());
		height = Math.floor($zenario_fbsk.height());
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
			
			if ($zenario_fbsk.length) {
				get('zenario_fbsk').className = zenarioA.getSKBodyClass();
			}
			
			//This line fixes a bug where the height of the floating div keeps changing when Storekeeper is opened
			//by specifically setting it to what it was read as
			if (!zenarioA.storekeeperWindow) {
				Math.floor($zenario_fbsk.height(height));
			}
			
			if (!zenarioO.focus || !zenarioO.focus.banner) {
				graphicGap = 0;
			}
			
			if (window.zenarioONotFull) {
				--colGap;
			}
			
			get('organizer_leftColumn').style.height = ((1*height - outerGap - colGap) - 2) + 'px';
			get('organizer_rightColumn').style.height = get('organizer_preloader_circle').style.height = (1*height - outerGap - colGap) + 'px';
			get('organizer_Outer').style.height = (1*height - outerGap) + 'px';
			
			if (get('organizer_colsortfields')) {
				if (zenario.browserIsIE(6)) {
					get('organizer_colsortfields').style.height = (1*height - outerGap) + 'px';
				} else {
					get('organizer_colsortfields').style.maxHeight = (1*height - outerGap) + 'px';
				}
			}
			
			var rightColumnContentHeight = ((1*height - outerGap - colGap - contentGap - graphicGap - buttonsGap - bordersWidth - zenarioO.columnPadding + titleGap - headerToolbar) - 13);
			if(get('organizer_rightColumnContent')) {
				get('organizer_rightColumnContent').style.height = rightColumnContentHeight + 'px';
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
			if (get('organizer_rightColumnContent')) get('organizer_rightColumnContent').style.width = (width - 30 - leftColAdjustment - bordersWidth) + 'px';
			
			
			if (window.zenarioOSelectMode) {
				get('organizer_choose_button').style.display = 'inline';
			} else {
				get('organizer_choose_button').style.display = 'none';
			}
			
			zenarioO.setPages();
			zenarioO.setPanelTitle();
			
			zenarioO.lastSize = width + 'x' + height;
			
			zenarioO.setTopLevelNavScrollStatus();
			
			zenarioO.adjustSlideDownViewInGridMode();
		}
	}
	
	//Stop selection/highlighting in IE
	if (zenario.browserIsIE()) {
		if (get('organizer_rightColumnContent')) {
			if (zenario.browserIsIE(8)) {
				get('organizer_Outer').onselectstart =
					function() {
						return false;
					};
			
			} else {
				get('organizer_leftColumnOuter').onselectstart =
				get('organizer_itemCountWrap').onselectstart =
					function() {
						return false;
					};
				get('organizer_rightColumn').onselectstart =
					function() {
						return !(zenarioO.itemsSelected > 1);
					};
			}
		}
	}
	
	
	zenarioO.sizing = setTimeout(zenarioO.size, 500);
};

//Slide-down views always need to be after a row of items.
	//In list view, this is easy, as each item takes up one row.
	//In grid view, we need to shuffle them around a bit depending on the number of items per row...
zenarioO.adjustSlideDownViewInGridMode = function() {
	
	if (zenarioO.view_mode == 'grid' && zenarioO.focus.slidedown_view_microtemplate) {
		var $el, numberThisRow = 0, maxInRow = 1, lastOffsetTop, offsetTop, slideDownViews = [], shownOrHidden = {};
		
		//Temporarily hide slide-down view if it is open
		$('#organizer_items_wrapper > div.organizer_slidedown_view').each(function(i, el) {
			shownOrHidden[el.id] = el.style.display;
			el.style.display = 'none';
		});
		
		//Count how many items are on each row
		$('#organizer_items_wrapper > div.organizer_item').each(function(i, el) {
			$el = $(el);
			offsetTop = $el.offset().top;
			if (lastOffsetTop !== undefined && lastOffsetTop != offsetTop) {
				maxInRow = Math.max(maxInRow, numberThisRow);
				numberThisRow = 1;
			} else {
				++numberThisRow;
			}
			lastOffsetTop = offsetTop;
		});
		maxInRow = Math.max(maxInRow, numberThisRow);
		
		//Detach all of the slidedowns, and save the detached slide-downs in an array
		$('#organizer_items_wrapper > div.organizer_slidedown_view').each(function(i, el) {
			$el = $(el);
			el.style.display = shownOrHidden[el.id];
			slideDownViews.push($el.detach());
		});
		
		//Loop through the items
		//When we get to the end of a row of items, add some of the slide-downs that we just detached back after that item
		numberThisRow = 0;
		$('#organizer_items_wrapper > div.organizer_item').each(function(i, el) {
			$el = $(el);
			if (++numberThisRow >= maxInRow) {
				zenarioO.adjustSlideDownViewInGridMode2(slideDownViews, $el, maxInRow);
				numberThisRow = 0;
			}
		});
		
		zenarioO.adjustSlideDownViewInGridMode2(slideDownViews, $el, maxInRow);
	}
};

//Add a row of slide-downs back after a row of items
zenarioO.adjustSlideDownViewInGridMode2 = function(slideDownViews, $lastEl, maxInRow) {
	var i, $el;
	for (i = 0; i < maxInRow && slideDownViews[0]; ++i) {
		$el = slideDownViews.shift();
		$lastEl.after($el);
		$lastEl = $el;
	}
};




	})(
		this,
		URLBasePath,
		window, document,
		zenario, zenarioA, zenarioTab, zenarioAT,
		zenario.get, zenario.engToBoolean, zenario.htmlspecialchars, zenario.ifNull, zenario.jsEscape, zenarioA.phrase);
};

