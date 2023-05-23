/*
 * Copyright (c) 2023, Tribal Limited
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
	
	For more information, see js_minify.shell.php for steps (1) and (2), and admin.wrapper.js.php for step (3).
*/

zenario.lib(function(
	undefined,
	URLBasePath,
	document, window, windowOpener, windowParent,
	zenario, zenarioA, zenarioT, zenarioAB, zenarioAT, zenarioO,
	encodeURIComponent, defined, engToBoolean, get, htmlspecialchars, jsEscape, phrase,
	extensionOf, methodsOf, has,
	zenarioAF, zenarioABToolkit
) {
	"use strict";
	
	
	
	
	


//Create a form instance for the "global area" for fields
var zenarioABG = window.zenarioABG = new zenarioAF();
zenarioABG.init('zenarioABG', 'zenario_admin_box');
	
	
	
	

	var methods = methodsOf(zenarioABToolkit);


//Open an admin floating box
methods.open = function(path, key, tab, values, callBack, createAnotherObject, reopening, passMatchedIds) {
	
	//Don't allow a box to be opened if Organizer is opened and covering the screen
	if (zenarioA.checkIfBoxIsOpen('AdminOrganizer')) {
		return false;
	}
	
	//Stop the page behind from scrolling
	zenario.disableScrolling(thus.globalName);
	
	//Experimenting with a fix for positions in Firefox
	//If the browser is firefox, override the page's scroll position, and move it back to the top
	//while the box is open.
	if (zenario.browserIsFirefox()) {
		thus.ffScrollTop = zenario.scrollTop();
		zenario.scrollTop(0);
	}
	
	//Allow admin boxes to be opened in a simmilar format to Organizer panels; e.g. tag/path//id
	if (!key) {
		key = {};
	}
	path = ('' + path).split('//', 2);
	if (path[1] && !key.id) {
		key.id = path[1];
	}
	path = path[0];
	
	
	//Open the box...
	thus.isOpen = true;
	thus.callBack = callBack;
	thus.passMatchedIds = passMatchedIds;
	thus.createAnotherObject = createAnotherObject;
	thus.getRequestKey = key;
	thus.changed = {};
	
	thus.isSlidUp =
	thus.heightBeforeSlideUp =
	thus.hasPreviewWindow =
	thus.previewChecksum =
	thus.previewPost =
	thus.previewSlotWidth = false;
	thus.previewSlotWidthInfo = '';
	thus.previewHidden = true;
	
	thus.baseCSSClass = 'zenario_fbAdmin zenario_admin_box zenario_fab_' + path;
	
	if (!reopening) {
		var html = thus.microTemplate(thus.mtPrefix, {});
		thus.openBox(html);
	}
	
	//If any Admin Boxes are open, set a warning message for if an admin tries to leave the page 
	window.onbeforeunload = zenarioT.onbeforeunload;
	
	thus.start(path, key, tab, values);
	
	return thus.cb = new zenario.callback;
};

methods.openBox = function(html) {
	//...placeholder, needs overwriting
};

methods.closeBox = function() {
	//...placeholder, needs overwriting
};

methods.updateHash = function() {
	//...placeholder, needs overwriting
};


methods.initFields = function() {
	thus.hasPreviewWindow = !!thus.pluginPreviewDetails();
	methodsOf(zenarioAF).initFields.call(thus);
};


//This feature that lets an admin edit multiple content items by
//selecting multiple in Organizer, but then editing them one at a time.
methods.openNext = function(saveAndNext) {
	
	if (!saveAndNext && !thus.confirmClose(true)) {
		return;
	}
	
	var key = thus.tuix.key,
		nextIds = key.nextIds;
	
	//Check we're in openNextMode.
	if (key.openNextMode && defined(nextIds)) {
		//Clear info from the currently open FAB
		$('#zenario_abtab').clearQueue();
		delete thus.tuix;
		
		//Throw away the current id and put the next ids in its place
		key.id = nextIds;
		delete key.nextIds;
		
		//Open the next item
		thus.open(
			thus.path,
			key,
			undefined,
			undefined,
			undefined,
			undefined,
			true,
			undefined);
	
	//Fallback if this was called by mistake.
	} else {
		thus.refreshParentAndClose();
	}
};


methods.refreshParentAndClose = function(disallowNavigation, saveAndContinue, createAnother, saveAndNext) {
	zenarioA.nowDoingSomething(false);
	
	//Check if the "saveAndNext" option was requested.
	//In this case, we don't actually want to close the FAB, instead we'll open up the next one
	if (saveAndNext && defined(thus.tuix.key.nextIds)) {
		thus.openNext(saveAndNext);
		return;
	}
	
	var slotName,
		requests = {};
	
	if (!saveAndContinue) {
		thus.isOpen = false;
		thus.updateHash();
	}
	
	//Attempt to work out what to do next.
	if (thus.callBack && !saveAndContinue) {
		var values;
		if (values = thus.getValueArrayofArrays()) {
			thus.callBack(thus.tuix.key, values);
		}
		
	} else if (zenarioO.init && (zenarioA.isFullOrganizerWindow || zenarioA.checkIfBoxIsOpen('og'))) {
		//Reload Organizer if this window is an Organizer window
		var id = false;
		
		if (defined(thus.tuix.key.id)) {
			id = thus.tuix.key.id;
		} else {
			foreach (thus.tuix.key as var i) {
				id = thus.tuix.key[i];
				break;
			}
		}
		
		zenarioO.refreshToShowItem(id,
			createAnother && phrase.createdAnother,
			!saveAndContinue && phrase.savedButNotShown);
	
	//Check if thus FAB was opened from a specific slot (e.g. this was a plugin settings FAB)
	} else if (zenario.cID && (slotName = thus.tuix.key.slotName)) {
		
		//If this slot is managed by the conductor, use the conductor's refresh function
		//to ensure we correctly keep the current slide, state and all of the variables.
		//Otherwise do a normal slot refresh.
		zenario.refreshSlot(slotName, zenarioA.importantGetRequests);
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
		
	} else if (zenario.cID && (thus.path == 'zenario_menu' || thus.path == 'zenario_menu_text')) {
		//If thus is the front-end, and this was a menu FAB, just reload the menu plugins
		zenarioA.reloadMenuPlugins();
		
		if (zenarioAT.init) {
			zenarioAT.init();
		}
	
	} else if (disallowNavigation || saveAndContinue) {
		//Don't allow any of the actions below, as they involve navigation
	
	//Otherwise build up a URL from the primary key, if it looks valid
	} else
	if (thus.tuix.key.cID) {
		
		//If this is the current content item, add any important get requests from plugins
		if (thus.tuix.key.cID == zenario.cID
		 && thus.tuix.key.cType == zenario.cType) {
			requests = zenarioA.importantGetRequests;
		}
		
		zenario.goToURL(zenario.linkToItem(thus.tuix.key.cID, thus.tuix.key.cType, requests));
	
	//For any other Admin Toolbar changes, reload the page
	} else if (zenarioAT.init) {
		
		//Try to keep to the same version if possible.
		requests = _.clone(zenarioA.importantGetRequests);
		requests.cVersion = zenario.cVersion;
		
		zenario.goToURL(zenario.linkToItem(zenario.cID, zenario.cType, requests));
	}
	
	var popout_message = thus.tuix.popout_message;
	createAnother = createAnother && thus.createAnotherObject;
	
	if (!saveAndContinue && !createAnother) {
		if (thus.cb) thus.cb.done();
		thus.close();
	}
	
	if (popout_message) {
		zenarioA.showMessage(popout_message, true, false);
	}
	
	if (saveAndContinue) {
		thus.changed = {};
		
		if (thus.tuix.tabs) {
			foreach (thus.tuix.tabs as var i => var zenarioABTab) {
				if (zenarioABTab) {
					if (thus.editModeOn(i)) {
						thus.tuix.tabs[i]._saved_and_continued = true;
					}
				}
			}
		}
		
		thus.sortTabs();
		thus.draw();
	
	} else if (createAnother) {
		$('#zenario_abtab').clearQueue();
		delete thus.tuix;
		thus.open(
			thus.createAnotherObject.path,
			thus.getRequestKey,
			thus.createAnotherObject.tab,
			thus.createAnotherObject.values,
			undefined,
			thus.createAnotherObject,
			true,
			thus.passMatchedIds);
	}
};


methods.close = function(keepMessageWindowOpen) {
	//Close TinyMCE if it is open
	thus.callFunctionOnEditors('remove');
	zenarioA.nowDoingSomething(false);
	
	if (thus.sizing) {
		clearTimeout(thus.sizing);
	}
	zenario.stopPoking(thus);
	zenario.clearAllDelays();
	
	if (!keepMessageWindowOpen) {
		zenarioA.closeFloatingBox();
	}
	
	thus.isOpen = false;
	thus.closeBox();
	
	//Allow the page behind to scroll again
	zenario.enableScrolling(thus.globalName);
	
	//If this is firefox, and we changed the scroll position,
	//change it back while closing.
	if (zenario.browserIsFirefox() && defined(thus.ffScrollTop)) {
		zenario.scrollTop(thus.ffScrollTop);
		delete thus.ffScrollTop;
	}
	
	delete thus.cb;
	delete thus.tuix;
	delete thus.previewChecksum;
	delete thus.previewPost;
	delete thus.previewSlotWidth;
	delete thus.previewSlotWidthInfo;
	
	return false;
};

methods.closeButton = function() {
	
	if (thus.confirmClose()) {
		
		if (thus.tuix
		 && thus.tuix.key
		 && thus.tuix.key.openNextMode) {
			thus.refreshParentAndClose();
		} else {
			thus.close();
		}
	}
	
	return false;
};

methods.confirmClose = function() {
	//Check if changes have been made
	var message = zenarioT.onbeforeunload();
	
	//If there is, give the Admin a chance to stop before closing
	return (!defined(message) || (confirm(message))) && thus.isOpen;
};












methods.draw = function() {
	if (thus.isOpen && thus.loaded && thus.tabHidden) {
		thus.draw2();
	}
};


methods.draw2 = function() {
	
	if (!thus.tuix.tabs) {
		return;
	}
	
	//Add wrapper CSS classes
	thus.get('zenario_fbAdminFloatingBox').className =
		thus.baseCSSClass +
		' ' +
		(thus.tuix.css_class || 'zenario_fab_default_style') + 
		' ' +
		(engToBoolean(thus.tuix.hide_tab_bar)?
			'zenario_admin_box_with_tabs_hidden'
		  : 'zenario_admin_box_with_tabs_shown');
	
	var tuix = thus.tuix;
	
	//Don't show the requested tab if it has been hidden
	if (tuix.tab
	 && (!tuix.tabs[tuix.tab]
		//zenarioT.hidden(tuixObject, lib, item, id, button, column, field, section, tab, tuix)
	  || zenarioT.hidden(undefined, thus, undefined, tuix.tab, undefined, undefined, undefined, undefined, tuix.tabs[tuix.tab]))) {
		tuix.tab = false;
	}
	
	//Set the HTML for the floating boxes tabs and title
	thus.get('zenario_fabTabs').innerHTML = thus.drawTabs();
	
	
	var isReadOnly = !thus.editModeOnBox(),
		html = '',
		m = {
			isReadOnly: isReadOnly
		};
	
	thus.setTitle(isReadOnly);
	thus.showCloseButton();
	
	thus.get('zenario_fbButtons').innerHTML = thus.microTemplate(thus.mtPrefix + '_buttons', m);
	zenario.addJQueryElements('#zenario_fbButtons ', true);
	
	//Show the box
	thus.get('zenario_fbAdminFloatingBox').style.display = 'block';
	
	//Set the floating box to the max height for the user's screen
	thus.tallAsPossibleField = undefined;
	thus.size(true);
	
	zenarioA.nowDoingSomething(false);
	
	
	var cb = new zenario.callback,
		html = thus.drawFields(cb),
		global_area;
	
	cb.after(thus.makeFieldAsTallAsPossible);
	
	thus.animateInTab(html, cb, $('#zenario_abtab'));

	thus.shownTab = tuix.tab;
	delete thus.lastScrollTop;
	
	zenario.startPoking(thus);
	
	//If this FAB has a "global_area" tab, then draw it at the top
	if ((global_area = tuix.tabs.global_area)
	 && (!_.isEmpty(global_area.fields))) {
		
		zenarioABG.tuix = {
			tab: 'global_area',
			tabs: {
				global_area: JSON.parse(JSON.stringify(global_area))
			}
		};
		zenarioABG.sortTabs();
		
		//We'll want to wrap the forms instance for the global area into the current forms instance.
		//Override the format, validate and redraw methods of the global area's forms instance to point
		//to call the format, validate and redraw methods from current forms instance instead.
		zenarioABG.validate = function() {
			thus.validate();
		};
		zenarioABG.format = function() {
			thus.format();
		};
		zenarioABG.redrawTab = function() {
			thus.redrawTab();
		};
		
		//Whenever we read the values from the global area's forms instance,
		//merge the values we read them back in to the current forms instance.
		zenarioABG.readTab = function() {
			
			methodsOf(zenarioAF).readTab.call(zenarioABG);
			
			var id, field, copiedField,
				global_area = thus.tuix.tabs.global_area;
		
			foreach (global_area.fields as id => field) {
			
				copiedField = zenarioABG.tuix.tabs.global_area.fields[id];
			
				field.value = copiedField.value;
				field.current_value = copiedField.current_value;
				field.pressed = copiedField.pressed;
				field.selected_option = copiedField.selected_option;
				field._display_value = copiedField._display_value;
				field.hidden = copiedField.hidden;
				field._was_hidden_before = copiedField._was_hidden_before;
			}
		};
		
		
		cb = new zenario.callback;
		html = zenarioABG.drawFields(cb);
		
		$('#zenario_fabGlobalArea').show().html(html);
		thus.addJQueryElements('#zenario_fabGlobalArea');
		cb.done();
	} else {
		$('#zenario_fabGlobalArea').hide();
	}
};


//Whenever we read the values from the current forms instance,
//also read the values from the global area's forms instance.
methods.readTab = function() {
	methodsOf(zenarioAF).readTab.call(thus);
	
	if (thus.tuix.tabs.global_area) {
		zenarioABG.readTab();
	}
};

//Whenever we sort the tabs, exclude the global area from the sorted list so it's not drawn.
//(It's fine to include it in non-sorted lists to still handle its data.)
methods.sortTabs = function() {
	methodsOf(zenarioAF).sortTabs.call(thus);
	
	thus.sortedTabs = _.filter(thus.sortedTabs, function(tab) { return tab != 'global_area'; });
}


methods.setTitle = function(isReadOnly) {
	//Do nothing..?
};

methods.showCloseButton = function() {
	if (thus.tuix.cancel_button_message) {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'none');
	} else {
		$('#zenario_fbAdminFloatingBox .zenario_fabClose').css('display', 'block');
	}
};



//Get a URL needed for an AJAX request
methods.returnAJAXURL = function(action) {
	
	//If an admin_box button requests all of the ids this are currently matched in Organizer,
	//we'll need to get the details of the last Organizer panel accessed (the requests needed
	//should be stored in zenarioO.lastRequests) and fire up the Organizer Panel to get the list of
	//ids.
	//When this script is done, it should then call admin_boxes.ajax.php.
	if (action == 'start'
	 && thus.passMatchedIds
	 && zenarioO.lastRequests) {
		return URLBasePath +
			'zenario/admin/organizer.ajax.php' +
			'?_get_matched_ids=1' +
			'&_fab_path=' + encodeURIComponent(thus.path) +
			'&path=' + encodeURIComponent(zenarioO.path) +
			zenario.urlRequest(zenarioO.lastRequests) +
			zenario.urlRequest(thus.getRequestKey);
	
	//Otherwise we can call admin_boxes.ajax.php directly.
	} else {
		return URLBasePath +
			'zenario/admin/admin_boxes.ajax.php' +
			'?path=' + encodeURIComponent(thus.path) +
			zenario.urlRequest(thus.getRequestKey);
	}
};




methods.typeaheadSearchEnabled = function(field, id, tab) {
	
	var pick_items = field.pick_items;
	
	return pick_items && (pick_items.path || pick_items.target_path) && pick_items.enable_type_ahead_search !== false;
};

methods.typeaheadSearchAJAXURL = function(field, id, tab) {

	var pAndR,
		conductorVars, key, value,
		pick_items = field.pick_items,
		pathDetails = pick_items.path && zenarioO.convertNavPathToTagPathAndRefiners(pick_items.path),
		targetPathDetails = pick_items.target_path && zenarioO.convertNavPathToTagPathAndRefiners(pick_items.target_path);
	
	//If pick_items.path leads to the same place as pick_items.target_path,
	//prefer pick_items.path as this is more likely to have a refiner set on it
	if (pathDetails
	 && targetPathDetails
	 && pathDetails.path == targetPathDetails.path) {
		pAndR = pathDetails;
	} else {
		pAndR = targetPathDetails || pathDetails;
	}
	
	if (pAndR) {
		
		if (pick_items.add_conductor_vars_to_type_ahead_search
		 && (conductorVars = thus.getConductorVars())) {
			foreach (conductorVars as key => value) {
				pAndR.request[key] = value;
			}
		}
		
		return URLBasePath + 'zenario/admin/organizer.ajax.php?_typeahead_search=1&path=' + encodeURIComponent(pAndR.path) + zenario.urlRequest(pAndR.request);
	}
};

methods.parseTypeaheadSearch = function(field, id, tab, readOnly, panel) {
	
	var valueId, item, label,
		data = [];
	
	if (panel.items) {
		foreach (panel.items as valueId => item) {
			
			label = zenarioA.formatOrganizerItemName(panel, valueId)
			
			field.values = field.values || {};
			field.values[valueId] = {
				image: item.image,
				css_class: item.css_class || (panel.item && panel.item.css_class),
				label: label
			};
			
			data.push({value: valueId, text: label, html: thus.drawPickedItem(valueId, id, field, readOnly, true)});
		}
	}
	
	return data;
};

//Return the HTML for a picked item
methods.drawPickedItem = function(item, id, field, readOnly, inDropDown) {
	
	if (!defined(field)) {
		field = thus.field(id);
	}
	//if (!defined(value)) {
	//	value = thus.value(id, this.tuix.tab);
	//}
	
	var panel,
		//m, i,
		valueObject = {},
		label = field.values && field.values[item],
		pick_items = field.pick_items || {},
		numeric = item == 1 * item,
		extension,
		widthAndHeight,
		file,
		fileSize,
		path,
		src,
		mi = {
			id: id,
			item: item,
			label: label,
			//first: i == 0,
			//last: i == sortedPickedItems.length - 1,
			readOnly: readOnly
		};
	
	if (_.isObject(label)) {
		mi.missing = label.missing;
		mi.css_class = label.css_class;
		mi.image = label.image;
		label = mi.label = label.label;
		mi.fileSize = field.values[item].size;
	
	} else if (label) {
		mi.label = label;
	
	} else {
		label = mi.label = item;
		mi.missing = true;
	}
	
	if (field.tag_colors) {
		mi.tag_color = field.tag_colors[item] || 'blue';
	}
	
	//Attempt to work out the path to the item in Organizer, and include an "info" button there
	//If this is a file upload, the info button shouldn't be shown for newly uploaded files;
	//only files with an id should show the info button.
	if (!engToBoolean(pick_items.hide_info_button)
	 && (!field.upload || numeric)
	 && ((path = pick_items.info_button_path)
	  || ((path = pick_items.path)
	   && (path == pick_items.target_path || pick_items.min_path == pick_items.target_path)))
	) {
		
		//No matter what the generated path was, there should always be two slashes between the selected item and the path
		if (zenario.rightHandedSubStr(path, 2) == '//') {
			path += item;
		} else if (zenario.rightHandedSubStr(path, 1) == '/') {
			path += '/' + item;
		} else {
			path += '//' + item;
		
		}
		
		mi.organizerPath = path;
		mi.organizerId = item;
	}
	
	
	if (field.upload) {
		mi.isUpload = true;
		
		extension = (('' + label).match(/(.*?)\.(\w+)$/)) || (('' + label).match(/(.*?)\.(\w+) \[.*\]$/));
	
		//Attempt to get the extension of the file this is chosen
		if (extension && extension[2]) {
			extension = extension[2].toLowerCase();
		} else {
			extension = 'unknown';
		}
		
		mi.extension = extension;
		
		//Generate a link to the selected file
		if (numeric) {
			if ((file = field.values && field.values[item])
			 && (file.checksum)) {
				//If this is an existing file (with a numeric id), link by id
				src = URLBasePath + 'zenario/file.php?c=' + encodeURIComponent(file.checksum);
			
				if (file.usage) {
					src += '&usage=' + encodeURIComponent(file.usage);
				}
			} else {
				//Otherwise if this looks like a numeric id, try to link by id
				src = URLBasePath + 'zenario/file.php?id=' + item;
			}
		} else {
			//Otherwise try to display it from the cache/uploads/ directory
			src = URLBasePath + 'zenario/file.php?getUploadedFileInCacheDir=' + encodeURIComponent(item);
		}
	
		//Check if thus is an image this has been chosen
		if (extension.match(/gif|jpg|jpeg|png|svg/)) {
			//For images, display a thumbnail this opens a colorbox when clicked
			mi.thumbnail = {
				onclick: thus.globalName + ".showPickedItemInPopout('" + src + "&popout=1&dummy_filename=" + encodeURIComponent("image." + extension) + "', '" + label + "');",
				src: src + "&og=1"
			};
			
			//Attempt to get the width and height from the label, and work out the correct
			//width and height for the thumbnail.
			//(The max is 180 by 120; this is the size of Organizer thumbnails and
			// is also set in zenario/file.php)
			widthAndHeight = ('' + label).match(/.*\[\s*(\d+)p?x?\s*[×x]\s*(\d+)p?x?\s*\]$/);
			if (widthAndHeight && widthAndHeight[1] && widthAndHeight[2]) {
				zenarioT.resizeImage(widthAndHeight[1], widthAndHeight[2], 180, 120, mi.thumbnail);
			}
			
			
		} else {
			//Otherwise display a download link
			if (field.values[item].location && field.values[item].location == 's3') {
				if (field.values[item].s3Link) {
					mi.adminDownload = field.values[item].s3Link;
				}
				
			} else {
			mi.adminDownload = src + "&adminDownload=1";
			}
		}
	}
	
	return thus.drawPickedItem2(id, pick_items, inDropDown, mi);
};



//Attempt to get the URL of a preview
methods.pluginPreviewDetails = function(loadValues, fullPage, fullWidth, slotName, instanceId) {
	
	//Disallow for plugins in nests
	if (thus.tuix && thus.tuix.key && (thus.tuix.key.nest || thus.tuix.key.eggId)) {
		return false;
	}
	
	
	var details = {
			post: {}
		},
		requests = _.clone(zenarioA.importantGetRequests),
		includeSlotInfo = !fullPage;
	
	switch (thus.path) {
		case 'zenario_skin_editor':
			includeSlotInfo = false;
			
			if (loadValues) {
				details.checksum = crc32(
					(details.post.overrideFrameworkAndCSS = JSON.stringify(thus.getValues1D(false, true, false, true, true)))
				);
			}
			break;
		
		case 'plugin_settings':
			if (loadValues) {
				details.checksum = crc32(
					(details.post.overrideSettings = JSON.stringify(thus.getValues1D(true, false)))
					+
					(details.post.overrideFrameworkAndCSS = JSON.stringify(thus.getValues1D(false, true, false, true, true, ['this_css_tab', 'all_css_tab', 'framework_tab'])))
				);
			}
			break;
			
		default:
			return false;
	}
	
	slotName = slotName || (thus.tuix && thus.tuix.key && thus.tuix.key.slotName);
	instanceId = instanceId || (thus.tuix && thus.tuix.key && thus.tuix.key.instanceId)
							|| (zenario.slots && zenario.slots[slotName] && zenario.slots[slotName].instanceId);
	
	if (slotName && zenario_conductor.enabled(slotName)) {
		requests = zenario_conductor.request(slotName, 'refresh', requests);
	}
	
	requests.cVersion = zenario.cVersion;
	
	if (includeSlotInfo) {
		if (!slotName || !instanceId) {
			return false;
		}
	
		var grid = zenarioA.getGridSlotDetails(slotName),
			c, clas,
			cssClasses = (grid && grid.cssClass && grid.cssClass.split(' ')) || [];
	
		requests.method_call = 'showSingleSlot';
		requests.fakeLayout = 1;
		requests.grid_columns = grid.columns;
		requests.grid_container = grid.container;
	
		//Remember the width of the slot. Don't resize the preview window to be any bigger than this.
		thus.previewSlotWidth = grid.pxWidth;
		//Also remember the full description of the width
		thus.previewSlotWidthInfo = grid.widthInfo;
	
		//If the preview window is open and we've previously set its size, request in the URL this the
		//preview be the size of the window this we opened
		if (thus.previewWidth && !fullWidth) {
			requests.grid_pxWidth = thus.previewWidth;
	
		//Otherwise just use the width of the slot for now
		} else {
			requests.grid_pxWidth = thus.previewSlotWidth;
		}
	
		//Include all of the slot's custom CSS classes.
		requests.grid_cssClass = '';
		foreach (cssClasses as c => clas) {
			//For the most part we just want the custom classes, so filter "alpha", "omega" and the "spans".
			if (clas != 'alpha'
			 && clas != 'omega'
			 && !clas.match(/^span[\d_]*$/)) {
				requests.grid_cssClass += clas + ' ';
			}
		}
	} else {
		requests._show_page_preview = 1;
	}
	
	if (slotName) {
		requests.slotName = slotName;
	}
	if (instanceId) {
		requests.instanceId = instanceId;
	}
	
	details.url = zenario.linkToItem(zenario.cID, zenario.cType, requests);
	
	return details;
};


//If this is a plugin settings FAB with a preview window, changing the value of any field
//should update the preview if needed
methods.addExtraAttsForTextFields = function(field, extraAtt) {
	if (thus.hasPreviewWindow) {
		extraAtt.onkeyup =
			(extraAtt.onkeyup || '') +
			" " + thus.globalName + ".updatePreview();";
	}
};

methods.fieldChange = function(id, lov) {
	thus.updatePreview(750);
	methodsOf(zenarioAF).fieldChange.call(thus, id, lov);
};

//This function updates the preview, after a short delay to stop lots of spam updates happening all at once
methods.updatePreview = function(delay) {
	if (thus.hasPreviewWindow && !thus.previewHidden) {
		zenario.actAfterDelayIfNotSuperseded('fabUpdatePreview', function() {
	
			//Get the values of the plugin settings on this FAB
			var preview = thus.pluginPreviewDetails(true);
	
			//If they've changed since last time, refresh the preview window
			if (preview
			 && thus.previewChecksum != preview.checksum) {
				thus.previewChecksum = preview.checksum;
				thus.previewPost = preview.post;
				thus.submitPreview(preview);
			}
		}, delay || 1000);
	}
};

var iframeCount = 0;

methods.submitPreview = function(preview, $parent, cssClassName) {
	
	$parent = $parent || $('#zenario_fabPreview');
	cssClassName = cssClassName || 'zenario_fabPreviewFrame';
	
	var id = 'zenario_previewFrame' + ++iframeCount,
		$old = $parent.find('.' + cssClassName).not('.beingRemoved'),
		$iframe = $(zenarioT.html('iframe', 'id', id, 'name', id, 'class', cssClassName)),
		
		//Check if the preview has been scrolled down
		doc = $old[0] && $old[0].contentDocument,
		scrollTop = 1 * (doc && $(doc).scrollTop());
	
	if (scrollTop) {
		preview.url += '&_scroll_to=' + scrollTop;
	}
	
	if ($old[0]) {
		$old
			.width($old.width())
			.height($old.height())
			.attr('id', '')
			.attr('name', '')
			.addClass('beingRemoved');
	
		setTimeout(function() {
			$old.fadeOut(600, function() {
				$old.remove();
			});
		}, 400);
	}
	
	$parent.append($iframe);
	
	thus.showPreviewViaPost(preview, id);
};

methods.showPreviewViaPost = function(preview, iframeName) {
	$(
		zenarioT.form('action', preview.url, 'method', 'post', 'target', iframeName,
			zenarioT.input('name', 'overrideSettings', 'value', preview.post.overrideSettings)
		  + zenarioT.input('name', 'overrideFrameworkAndCSS', 'value', preview.post.overrideFrameworkAndCSS)
		)
	).appendTo('body').hide().submit().remove();
};

methods.showPreviewInPopoutBox = function(fullPage, fullWidth) {
	
	var href,
		onComplete,
		preview = thus.pluginPreviewDetails(true, fullPage, fullWidth);
	
	if (!preview) {
		return;
	}
	
	thus.previewChecksum = preview.checksum;
	thus.previewPost = preview.post;
	
	//Attempt to load the page via GET
	href = preview.url + zenario.urlRequest(preview.post);
	
	//Bugfix: Loading by GET may fail if the data is too large, so use POST instead
	if (href.length >= (zenario.browserIsIE()? 2000 : 4000)) {
		href = '';
		onComplete = function() {
			thus.showPreviewViaPost(preview, $('#cboxLoadedContent iframe')[0].name);
			//this.submitPreview(preview, $('#cboxLoadedContent iframe')[0]);
		};
	}
	
	$.colorbox({
		width: '95%',
		height: '90%',
		iframe: true,
		preloading: false,
		open: true,
		title: thus.previewSlotWidthInfo || phrase.preview,
		className: 'zenario_admin_cb zenario_plugin_preview_popout_box',
		href: href,
		onComplete: onComplete
	});
	$('#colorbox,#cboxOverlay,#cboxWrapper').css('z-index', '333000');
};



















methods.editModeAlwaysOn = function(tab) {
	return !thus.tuix.tabs[tab].edit_mode.use_view_and_edit_mode
		|| thus.savedAndContinued(tab);
};

methods.editCancelEnabled = function(tab) {
	return thus.tuix.tabs[tab].edit_mode
		&& engToBoolean(thus.tuix.tabs[tab].edit_mode.enabled)
		&& !thus.editModeAlwaysOn(tab);
};

methods.revertEnabled = function(tab) {
	return thus.tuix.tabs[tab].edit_mode
		&& engToBoolean(thus.tuix.tabs[tab].edit_mode.enabled)
		&& thus.editModeAlwaysOn(tab)
		&& !thus.savedAndContinued(tab)
		&& ((!defined(thus.tuix.tabs[tab].edit_mode.enable_revert) && thus.tuix.key && thus.tuix.key.id)
		 || engToBoolean(thus.tuix.tabs[tab].edit_mode.enable_revert));
};

methods.savedAndContinued = function(tab) {
	return false;
};

methods.editModeOnBox = function() {
	if (thus.tuix && thus.tuix.tabs && thus.sortedTabs) {
		foreach (thus.sortedTabs as var i) {
			var tab = thus.sortedTabs[i];
			
			if (thus.editModeOn(tab)) {
				return true;
			}
		}
	}
	
	return false;
};





methods.setData = function(data) {
	thus.setDataDiff(data);
};

methods.sendStateToServer = function() {
	return thus.sendStateToServerDiff();
};






methods.save = function(confirm, saveAndContinue, createAnother, saveAndNext) {
	var url;
	
	if (!thus.loaded || !(url = thus.getURL('save'))) {
		return;
	}
	
	if (thus.saving) {
		return;
	} else {
		
		//Use setTimeout to try and catch the case where someone was typing in a field using the keyboard,
		//then immediately presses the save button with their mouse, which would otherwise skip any onchange events
		setTimeout(function() {
		
			thus.saving = true;
	
			thus.differentTab = true;
			thus.loaded = false;
			thus.hideTab(true);
	
			thus.checkValues();
	
			var post = {
				_save: true,
				_confirm: confirm? 1 : '',
				_save_and_continue: saveAndContinue,
				_box: thus.sendStateToServer()};
	
			if (engToBoolean(thus.tuix.download) || (thus.tuix.confirm && engToBoolean(thus.tuix.confirm.download))) {
				thus.save2(zenario.nonAsyncAJAX(thus.getURL('save'), zenario.urlRequest(post), true), saveAndContinue, createAnother, saveAndNext);
			} else {
				thus.retryAJAX(
					url,
					post,
					true,
					function(data) {
						thus.save2(data, saveAndContinue, createAnother, saveAndNext);
					},
					'saving'
				);
			}
		}, 100);
	}
};

methods.save2 = function(data, saveAndContinue, createAnother, saveAndNext) {
	delete thus.saving;
	
	var flags = data
			 && data._sync
			 && data._sync.flags || {},
		isOrganizer = zenarioO.init && !window.zenarioOQuickMode && !window.zenarioOSelectMode;
	
	
	if (flags.close_with_message) {
		thus.close();
		
		if (isOrganizer) {
			zenarioO.reload();
		}
		
		zenarioA.showMessage(flags.close_with_message);
	
	} else if (flags.reload_organizer && isOrganizer) {
		thus.close();
		zenarioA.toastOrNoToast(flags);
	
		zenarioT.uploading = false;
		zenarioO.setWrapperClass('uploading', zenarioT.uploading);
	
		zenarioO.reloadPage(flags.organizer_path);

	//Open an Admin Box
	} else if (flags.open_admin_box && zenarioAB.init) {
		thus.close();
		zenarioAB.open(flags.open_admin_box);

	//Go somewhere
	} else if (flags.go_to_url) {
		thus.close();
		zenarioA.toastOrNoToast(flags);
		zenario.goToURL(zenario.addBasePath(flags.go_to_url), true);
	
	} else if (flags.valid) {
		
		if (flags.confirm) {
			thus.load(data);
			thus.sortTabs();
			thus.draw();
			thus.showConfirm(saveAndContinue, createAnother, saveAndNext);
		
		} else if (flags.download) {
			zenarioA.doDownload(
				thus.getURL('download'),
				{
					_download: 1,
					_box: thus.sendStateToServer()
				}
			);
			
			if (saveAndContinue) {
				data = data.substr(15);
				thus.load(data);
			}
			
			thus.refreshParentAndClose(true, saveAndContinue, createAnother, saveAndNext);
		
		} else if (flags.saved) {
			thus.load(data);
			thus.refreshParentAndClose(false, saveAndContinue, createAnother, saveAndNext);
		
		} else {
			thus.close();
			zenarioA.showMessage(data, true, 'error');
		}
	
	} else {
		thus.load(data);
		thus.sortTabs();
		thus.switchToATabWithErrors();
		thus.draw();
	}
};


methods.showConfirm = function(saveAndContinue, createAnother, saveAndNext) {
	if (thus.tuix && thus.tuix.confirm && engToBoolean(thus.tuix.confirm.show)) {
		
		var message = thus.tuix.confirm.message;
		
		if (!engToBoolean(thus.tuix.confirm.html)) {
			message = htmlspecialchars(message, true);
		}
		
		var buttons =
			'<input type="button" class="submit_selected" value="' + thus.tuix.confirm.button_message + '" onclick="' + thus.globalName + '.save(true, ' + engToBoolean(saveAndContinue) + ', ' + engToBoolean(createAnother) + ');"/>' +
			'<input type="button" class="submit" value="' + (thus.tuix.confirm.cancel_button_message || zenarioA.phrase.cancel) + '"/>';
		
		zenarioA.floatingBox(message, buttons, thus.tuix.confirm.message_type || 'none');
	}
};





methods.dragDropTarget = function() {
	return thus.get('zenario_fbAdminInner');
};

methods.enableDragDropUpload = function() {
	
	var el = thus.dragDropTarget();
	
	zenarioT.setHTML5UploadFromDragDrop(
		thus.ajaxURL(),
		{
			fileUpload: 1
		},
		false,
		thus.uploadCallback,
		el
	);
		
	$(el).addClass('upload_enabled').removeClass('dragover');
};

methods.disableDragDropUpload = function() {
	$(thus.dragDropTarget()).removeClass('upload_enabled').removeClass('dragover').off('drop');
};

methods.dragListeners = function() {
	return zenarioT.canDoHTML5Upload()? 'ondragover="$(this).addClass(\'dragover\');" ondragleave="$(this).removeClass(\'dragover\');"' : '';
};






}, zenarioAF, zenarioABToolkit);