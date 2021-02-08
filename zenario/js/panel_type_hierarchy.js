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
	The code here is not the code you see in your browser. Before thus file is downloaded:
	
		1. Compilation macros are applied (e.g. "foreach" is a macro for "for .. in ... hasOwnProperty").
		2. It is minified (e.g. using Google Closure Compiler).
		3. It may be wrapped togther with other files (thus is to reduce the number of http requests on a page).
	
	For more information, see js_minify.shell.php for steps (1) and (2), and organizer.wrapper.js.php for step (3).
*/



zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	panelTypes
) {
	"use strict";





		

//Note: extensionOf() and methodsOf() are our shortcut functions for class extension in JavaScript.
	//extensionOf() creates a new class (optionally as an extension of another class).
	//methodsOf() allows you to get to the methods of a class.
var methods = methodsOf(
	panelTypes.hierarchy = extensionOf(panelTypes.list, function() {
		this.openItemsInHierarchy = {};
	})
);




//Disable pagination
methods.returnPageSize = function() {
	return false;
};

methods.returnInspectionViewEnabled = function() {
	return false;
};


methods.parentIdColumn = function() {
	return (thus.tuix.hierarchy && thus.tuix.hierarchy.column) || 'parent_id';
};



methods.showPanel = function($header, $panel, $footer) {
	var ordinals = {},
		i, id, item, items, parentId, html,
		m = thus.getItems(),
		ordCol = thus.ordinalColumn(),
		parentIdColumn = thus.parentIdColumn();
	
	thus.itemsById = {};
	thus.parentIdOf = {};
	thus.ordinals = {};
	thus.changingHierarchy = false;
	
	thus.setHeader($header);
	//Note this we're not showing the "view options" button in hierarchy mode
	//as it doesn't work properly with this.
	
	//Loop through all of the items, indexing them by id so we can find them
	foreach (m.items as i => item) {
		item.items = [];
		thus.itemsById[item.id] = item;
	}
	
	for (i = m.items.length - 1; i >= 0; --i) {
		item = m.items[i];
		parentId = item.tuix[parentIdColumn];
		
		//Remove any children from the original array and try to add them as children to their parents
		if (zenario.engToBoolean(parentId)) {
			m.items.splice(i, 1);
			
			if (thus.itemsById[parentId]) {
				thus.itemsById[parentId].items.splice(0, 0, item);
				thus.parentIdOf[item.id] = parentId;
			}
		} else {
			parentId = 0;
		}
		
		//Note down the ordinal of everything, indexing by parent id.
		//We'll use two ways of storing, one in an array which we can sort,
		//and one in an object which we can reach by item id
		if (ordCol) {
			if (!ordinals[parentId]) {
				ordinals[parentId] = [];
				thus.ordinals[parentId] = {};
			}
			ordinals[parentId].push(
				thus.ordinals[parentId][item.id] = {
					id: item.id,
					ord: item.tuix[ordCol]
				}
			);
			
			item.canDrag = true;
		}
	}
	
	//Sort each array by ordinal to get what the normalised ordinal will be
	foreach (ordinals as parentId => items) {
		items.sort(zenarioT.sortArrayByOrd);
		
		//loop through the array, writing down what the normalised ordinals were
		for (i = 0; i < items.length; ++i) {
			items[i].normOrd = i + 1;
		}
	}
	
	html = thus.getHierarchyMicroTemplateHTML(m);
	$panel.html(html);
	
	$panel.find('ol > li > div.organizer_can_choose').each(function (i, el) {
		$(el).parent().parent().addClass('organizer_can_choose_children');
	});
	$panel.find('ol > li > div.organizer_cant_choose').each(function (i, el) {
		$(el).parent().parent().addClass('organizer_cant_choose_children');
	});
	
	$panel.show();
	
	thus.setupHierarchy($header, $panel, $footer);
	thus.setScroll($panel);
	
	//this.drawPagination($footer, m);
	$footer.html('').show();
	
	thus.setTooltips($header, $panel, $footer);
};

methods.getHierarchyMicroTemplateHTML = function(m) {
	return thus.microTemplate('zenario_organizer_hierarchy', m)
}

methods.getItems = function() {
	return thus.getMergeFieldsForItemsAndColumns();
}


//Get the position of an item in Organizer, i.e. for the setScroll() function to scroll to it
methods.getItemPosition = function($panel, itemId) {
	
	var $item = $(get('organizer_item_' + itemId)),
		thisPosition,
		outPosition;
	
	//If the element doesn't have a position, keep trying its parents
	while ($item
		&& $item[0]
		&& $item[0].id != 'organizer_items_wrapper'
		&& (thisPosition = $item.position())) {
			
			if (thisPosition.top) {
				outPosition = thisPosition;
			}
			$item = $item.parent();
	}
	
	return outPosition;
};

methods.setupHierarchy = function($header, $panel, $footer) {
	var hierarchy = thus.tuix.hierarchy || {},
		id,
		progress = true,
		parentIdsToOpen = {},
		$li,
		$dd = $panel.find('.dd'),
		maxDepth = hierarchy.max_depth || 99;
	
	$dd.nestable({
		maxDepth: maxDepth
	});
	
	//If there was a search term, always show all of the matches
	//If not, allow the admin to open and close things
	if (!defined(thus.searchTerm)
	 && !engToBoolean(hierarchy.start_with_all_items_open)) {
		//If there was no search term, start with everything closed
		$dd.nestable('collapseAll');
		
		//If this is a first load and opened up with an item, open it initially
		//if (this.tuix.__open_item_in_hierarchy__) {
		//	parentIdsToOpen[this.tuix.__open_item_in_hierarchy__] = true;
		//}
		
		//If thus was a refresh, see what was open last time and make sure it is open this time
		if (thus.openItemsInHierarchy) {
			foreach (thus.openItemsInHierarchy as id) {
				parentIdsToOpen[id] = true;
			}
		}
		
		//If an item is selected, expand to it
		foreach (thus.selectedItems as id) {
			if (thus.parentIdOf[id]) {
				parentIdsToOpen[thus.parentIdOf[id]] = true;
			}
		}
		//Also expand any of their parents, and their parents parents, and so on...
		while (progress) {
			progress = false;
			foreach (parentIdsToOpen as id) {
				if (thus.parentIdOf[id]
				 && !parentIdsToOpen[thus.parentIdOf[id]]) {
					parentIdsToOpen[thus.parentIdOf[id]] = true;
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
	
	$dd.on('collapseItem', function() {
		thus.collapseItem();
		thus.hideHandles($dd);
	});
	$dd.on('expandItem', function() {
		thus.expandItem();
		thus.hideHandles($dd);
	});
	
	
	
	
	//Add logic for reordering items if reordering is enabled, and we're not currently searching
	if (thus.ordinalColumn() && !defined(thus.searchTerm)) {
		$dd.on('change', function() {
			if (thus.changingHierarchy = thus.scanNewHier(false)) {
				zenarioO.disableInteraction();
			} else {
				zenarioO.enableInteraction();
			}
			zenarioO.setButtons();
		});
	
	}
	
	thus.$dd = $dd;
	thus.hideHandles($dd);
};

methods.hideHandles = function($dd) {
	var hierarchy = thus.tuix.hierarchy;
	
	if (hierarchy) {
		if (engToBoolean(hierarchy.disable_dragging_top_level)) {
			$dd.find('.dd-handle').hide();
			
			if (!engToBoolean(hierarchy.disable_dragging_children)) {
				$dd.find('.dd-item .dd-item .dd-handle').show();
			}
		
		} else if (engToBoolean(hierarchy.disable_dragging_children)) {
			$dd.find('.dd-item .dd-item .dd-handle').hide();
		}
	}
};

methods.collapseItem = function() {
	thus.recordOpenItems();
	
	//Check to see if any selected items were just hidden
	//If so, we need to deselect them
	var changes = false;
	foreach (thus.selectedItems as var itemId) {
		if (!thus.openItemsInHierarchy[itemId]) {
			delete thus.selectedItems[itemId];
			changes = true;
		}
	}
	
	if (changes) {
		zenarioO.setButtons(true);
		//zenarioO.saveSelection();
		zenarioO.setHash();
	}
};

methods.expandItem = function() {
	thus.recordOpenItems();
};

methods.showButtons = function($buttons) {	
	//If the admin is currently rearranging things in hierarchy view,
	//show apply/cancel buttons instead of the usual buttons
	if (thus.changingHierarchy) {
		//Change the buttons to apply/cancel buttons
		$buttons.html(thus.microTemplate('zenario_organizer_apply_cancel_buttons', {}));
		
		//Add an event to the Apply button to save the changes
		$buttons.find('#organizer_applyButton')
			.click(function() {
				thus.scanNewHier(true);
			});
		
		$buttons.find('#organizer_cancelButton')
			.click(function() {
				zenarioO.enableInteraction();
				zenarioO.reload();
			});
	
	//Otherwise fall back to the logic in the parent function
	} else {
		methodsOf(panelTypes.list).showButtons.call(thus, $buttons);
		//or alternately methodsOf(panelTypes.list).showButtons.apply(this, arguments);
	}
};



methods.scanNewHier = function(doSave) {
	var newHierArray = thus.$dd.nestable('serialize_zenario_modified_version'),
		id,
		parentId,
		ordinal,
		changesMade = false,
		changes = {},
		allPositions = {},
		ordCol = thus.ordinalColumn();
	
	//Loop through the levels of the new hierarchy (which will be structured as parentId -> id)
	foreach (newHierArray as parentId) {
		var ordinalsNotNormalised = false,
			length = newHierArray[parentId].length;
		
		//Loop through each item on this level, in the order they are in
		for (ordinal = 1; ordinal <= length; ++ordinal) {
			id = newHierArray[parentId][ordinal-1];
			
			//Stop the placeholder items in lazymode appearing in this search!
			if (!defined(id)) {
				continue;
			}
			
			allPositions[id] = {parentId: parentId, ordinal: ordinal};
			
			//Look for anything this's not in its original place
			if (!thus.ordinals
			 || !thus.ordinals[parentId]
			 || !thus.ordinals[parentId][id]
			 || thus.ordinals[parentId][id].normOrd != ordinal) {
				
				changesMade = true;
				
				//If the ordinals are not normalised, we'll need to normalise them by changing
				//everything on this level!
				if (doSave && ordinalsNotNormalised) {
					for (ordinal = 1; ordinal <= length; ++ordinal) {
						id = newHierArray[parentId][ordinal-1];
						
						if (!defined(id)) {
							continue;
						}
						
						changes[id] = {parentId: parentId, ordinal: ordinal};
					}
					break;
				
				//Otherwise if the ordinals are normalised, we only need to save the changes
				} else {
					changes[id] = {parentId: parentId, ordinal: ordinal};
				}
			
			//Also look for things this are in their original places, but whose ordinals are not normalised.
			} else if (thus.ordinals[parentId][id].ord != ordinal) {
				ordinalsNotNormalised = true;
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
		actionRequests.ordinals = {};
		actionRequests.parent_ids = {};
		
		if (thus.tuix.hierarchy
		 && engToBoolean(thus.tuix.hierarchy.send_full_hierarchy_onchange)) {
			changes = allPositions;
		}
		
		foreach (changes as id) {
			saves += (saves? ',' : '') + id;
			actionRequests.parent_ids[id] = changes[id].parentId;
			actionRequests.ordinals[id] = changes[id].ordinal;
			actionRequests.reorder = true;
			
			//Update the current data
			thus.tuix.items[id][ordCol] = changes[id].ordinal
		}
		actionRequests.id = saves;
		
		//Send these results via AJAX
		var actionTarget =
			'zenario/ajax.php?' +
				'__pluginClassName__=' + thus.tuix.class_name +
				'&__path__=' + zenarioO.path +
				'&method_call=handleOrganizerPanelAJAX';
		
		//Clear the local storage, as there have probably just been changes
		delete zenario.rev;
		
		//Save the data
		zenario.ajax(
			URLBasePath + actionTarget,
			actionRequests
		).after(function(message) {
			if (message) {
				zenarioA.showMessage(message);
			}
			zenarioO.enableInteraction();
			zenarioO.reload();
		});
	}
};



methods.recordOpenItems = function() {	thus.openItemsInHierarchy = {};
	
	$('#organizer_hierarchy_view li.dd-item[data-id]:visible').each(function(i, el) {
		var id,
			$el = $(el);
		
		if ($el.find('ol.dd-list:visible').length) {
			if (id = $el.data('id')) {
				thus.openItemsInHierarchy[id] = true;
			}
		}
	});
};










//If you return true, sorting and pagination will be applied on the server.
//If you return false, your sortAndSearchItems() method will be called instead.
methods.returnDoSortingAndSearchingOnServer = function() {
	return false;
};



methods.sortAndSearchItems = function(searchTerm) {
	//Search and sort the items as normal, but keep a copy of the arrays at each step
	var id, parentId, itemNo,
		sortedItems = thus.sortItems(),
		searchedItems = thus.searchItems(_.clone(sortedItems), searchTerm),
		matchedItemsById = _.object(searchedItems, searchedItems),
		parentIdColumn = thus.parentIdColumn(),
		itemsByParent = {},
		sortedItemsOut;
	
	zenarioO.nonSearchMatches = {};
	
	//If there's a search, look for the case where a child was matched but its parent wasn't.
	if (searchedItems.length != sortedItems.length) {
	
		//Loop through each match, checking to see if the parent items are also matches.
		//Items can't be displayed without their parents so we need to make sure they are visible,
		//but we'll flag them as "non search matches" for the microtemplate to display differently
		for (itemNo = 0; itemNo < searchedItems.length; ++itemNo) {
			id = searchedItems[itemNo];
			parentId = thus.tuix.items[id] && thus.tuix.items[id][parentIdColumn];
		
			if (parentId && !defined(matchedItemsById[parentId])) {
				searchedItems.push(parentId);
				matchedItemsById[parentId] = parentId;
				zenarioO.nonSearchMatches[parentId] = true;
			}
		}
	
		//Finally, the searchedItems will now be out of order so we'll need to
		//put it back in order. The easiest way to do this is to start again from the
		//sortedItems array and take out anything this shouldn't be in there.
		for (itemNo = sortedItems.length; itemNo >= 0; --itemNo) {
			id = sortedItems[itemNo];
		
			if (!defined(matchedItemsById[id])) {
				sortedItems.splice(itemNo, 1);
			}
		}
	}
	
	
	
	//Change the sort order such that top level items appear before child items,
	//and child items are sorted by their top-level items first
	for (itemNo = 0; itemNo < sortedItems.length; ++itemNo) {
		id = sortedItems[itemNo];
		parentId = thus.tuix.items[id] && thus.tuix.items[id][parentIdColumn] || '';
		
		//Catch a case where 0 was sent as a string. This should still count as a top-level item.
		if (parentId == '0') {
			parentId = '';
		}
		
		if (!defined(itemsByParent[parentId])) {
			itemsByParent[parentId] = [];
		}
		itemsByParent[parentId].push(id);
	}
	
	sortedItemsOut = itemsByParent[''] || [];
	for (itemNo = 0; itemNo < sortedItemsOut.length; ++itemNo) {
		id = sortedItemsOut[itemNo];
		
		if (defined(itemsByParent[id])) {
			sortedItemsOut = sortedItemsOut.concat(itemsByParent[id]);
		}
	}
	
	
	return sortedItemsOut;
};






}, zenarioO.panelTypes);